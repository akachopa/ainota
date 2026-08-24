<?php

namespace App\Jobs\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\UploadBatch;
use App\Services\DocumentStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkNeedsReviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentId)
    {
        $this->onQueue('accounting');
    }

    public function handle(DocumentStatusService $statuses): void
    {
        $document = Document::query()->findOrFail($this->documentId);

        if (in_array($document->status, [
            DocumentStatus::DuplicateExact,
            DocumentStatus::Failed,
            DocumentStatus::Approved,
            DocumentStatus::NeedsReview,
        ], true)) {
            $document->update([
                'progress' => $document->status === DocumentStatus::NeedsReview ? 85 : $document->progress,
                'processing_completed_at' => now(),
            ]);

            $this->touchBatch($document);

            return;
        }

        $statuses->transition($document, DocumentStatus::NeedsReview, progress: 85);
        $document->update(['processing_completed_at' => now()]);
        $this->touchBatch($document);
    }

    private function touchBatch(Document $document): void
    {
        if (! $document->batch_id) {
            return;
        }

        $batch = UploadBatch::query()->find($document->batch_id);
        if (! $batch) {
            return;
        }

        $done = $batch->documents()->whereIn('status', [
            DocumentStatus::NeedsReview->value,
            DocumentStatus::DuplicateExact->value,
            DocumentStatus::DuplicatePossible->value,
            DocumentStatus::Failed->value,
            DocumentStatus::Approved->value,
            DocumentStatus::WaitingApproval->value,
        ])->count();
        $failed = $batch->documents()->where('status', DocumentStatus::Failed->value)->count();
        $batch->update([
            'processed_files' => $done,
            'failed_files' => $failed,
            'status' => $done >= $batch->total_files ? 'completed' : 'processing',
            'completed_at' => $done >= $batch->total_files ? now() : null,
        ]);
    }
}
