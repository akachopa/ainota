<?php

namespace App\Jobs\Documents;

use App\Enums\DocumentStatus;
use App\Enums\FailureCode;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Services\DocumentStatusService;
use App\Support\ImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PrepareDocumentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $documentId)
    {
        $this->onQueue('uploads');
    }

    public function uniqueId(): string
    {
        return 'prepare:'.$this->documentId;
    }

    public function handle(DocumentStatusService $statuses, ImageProcessor $processor): void
    {
        $document = Document::query()->findOrFail($this->documentId);
        $document->update(['processing_started_at' => now()]);
        $statuses->transition($document, DocumentStatus::DuplicateChecking, progress: 15);

        $absolute = Storage::disk($document->storage_disk)->path($document->storage_path);

        if ($processor->isEncryptedPdf($absolute, $document->mime_type)) {
            $statuses->transition($document, DocumentStatus::Failed, attributes: [
                'failure_code' => FailureCode::EncryptedPdf,
                'failure_message' => 'PDF terenkripsi tidak didukung pada MVP.',
            ]);
            $this->chained = [];

            return;
        }

        $pages = $processor->pageCount($absolute, $document->mime_type);
        $maxPages = (int) config('ainota.documents.max_pdf_pages');
        if ($pages > $maxPages) {
            $statuses->transition($document, DocumentStatus::Failed, attributes: [
                'failure_code' => FailureCode::TooManyPages,
                'failure_message' => "PDF melebihi batas {$maxPages} halaman.",
            ]);
            $this->chained = [];

            return;
        }

        $previewRel = "workspaces/{$document->workspace_id}/documents/{$document->id}/v{$document->current_version}/preview.jpg";
        $aiRel = "workspaces/{$document->workspace_id}/documents/{$document->id}/v{$document->current_version}/ai.jpg";
        $previewAbs = Storage::disk($document->storage_disk)->path($previewRel);
        $aiAbs = Storage::disk($document->storage_disk)->path($aiRel);

        $processor->writeJpegPreview($absolute, $previewAbs, 1280);
        $processor->writeJpegPreview($absolute, $aiAbs, 1600);

        $hashSource = is_file($aiAbs) ? $aiAbs : $absolute;
        $pHash = $processor->perceptualHash($hashSource);

        $document->update([
            'page_count' => $pages,
            'perceptual_hash' => $pHash,
        ]);

        $version = $document->currentVersionRecord;
        $version?->update([
            'preview_path' => is_file($previewAbs) ? $previewRel : null,
            'ai_optimized_path' => is_file($aiAbs) ? $aiRel : null,
        ]);

        for ($page = 1; $page <= $pages; $page++) {
            DocumentPage::query()->updateOrCreate(
                ['document_id' => $document->id, 'page_number' => $page],
                [
                    'workspace_id' => $document->workspace_id,
                    'document_version_id' => $version?->id,
                    'preview_path' => $page === 1 ? ($version?->preview_path) : null,
                    'ai_optimized_path' => $page === 1 ? ($version?->ai_optimized_path) : null,
                ],
            );
        }
    }
}
