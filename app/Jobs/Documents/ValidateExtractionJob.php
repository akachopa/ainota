<?php

namespace App\Jobs\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\DocumentStatusService;
use App\Services\ExtractionValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentId)
    {
        $this->onQueue('accounting');
    }

    public function handle(ExtractionValidationService $validator, DocumentStatusService $statuses): void
    {
        $document = Document::query()->with('latestExtraction')->findOrFail($this->documentId);
        $data = $document->latestExtraction?->normalized_data ?? [];
        $flags = array_values(array_unique(array_merge($document->flags ?? [], $validator->flags($data))));
        $document->update(['flags' => $flags]);

        $blocking = array_intersect($flags, ['TOTAL_MISMATCH', 'MISSING_GRAND_TOTAL', 'UNREADABLE_DOCUMENT']);
        if ($blocking !== [] && $document->status === DocumentStatus::AiProcessed) {
            $statuses->transition($document, DocumentStatus::ValidationFailed, progress: 78);
        }
    }
}
