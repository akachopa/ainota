<?php

namespace App\Services;

use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Jobs\Documents\RunDocumentPipelineJob;
use App\Models\Document;
use App\Models\DocumentHash;
use App\Models\DocumentVersion;
use App\Models\UploadBatch;
use App\Models\UploadLink;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentUploadService
{
    public function __construct(
        private ActivityLogger $logger,
    ) {}

    public function createBatch(Workspace $workspace, ?User $user, int $total, ?string $name = null): UploadBatch
    {
        return UploadBatch::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user?->id,
            'name' => $name ?: 'Batch '.now()->format('Ymd-His'),
            'total_files' => $total,
            'status' => 'processing',
        ]);
    }

    public function store(
        Workspace $workspace,
        UploadedFile $file,
        ?User $user = null,
        DocumentSource $source = DocumentSource::App,
        ?UploadBatch $batch = null,
        ?UploadLink $link = null,
        ?string $guestName = null,
    ): Document {
        $disk = (string) config('ainota.documents.disk');
        $documentId = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = "workspaces/{$workspace->id}/documents/{$documentId}/v1/original.{$extension}";
        Storage::disk($disk)->put($path, $file->get());

        $sha = hash('sha256', (string) Storage::disk($disk)->get($path));

        $document = Document::query()->create([
            'id' => $documentId,
            'workspace_id' => $workspace->id,
            'uploaded_by' => $user?->id,
            'batch_id' => $batch?->id,
            'upload_link_id' => $link?->id,
            'source' => $source,
            'status' => DocumentStatus::Queued,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'storage_disk' => $disk,
            'storage_path' => $path,
            'sha256' => $sha,
            'current_version' => 1,
            'page_count' => 1,
            'progress' => 5,
            'guest_uploader_name' => $guestName,
            'flags' => [],
        ]);

        DocumentVersion::query()->create([
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'version' => 1,
            'storage_path' => $path,
            'sha256' => $sha,
            'is_active' => true,
            'created_by' => $user?->id,
        ]);

        DocumentHash::query()->create([
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'hash_type' => 'sha256',
            'hash_value' => $sha,
        ]);

        if ($link) {
            $link->increment('upload_count');
        }

        $this->logger->log('document.uploaded', $workspace, $user, $document, after: [
            'filename' => $document->original_filename,
            'source' => $source->value,
        ]);

        RunDocumentPipelineJob::dispatch($document->id)->onQueue('uploads');

        return $document;
    }
}
