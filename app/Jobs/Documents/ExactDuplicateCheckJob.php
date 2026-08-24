<?php

namespace App\Jobs\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\DocumentStatusService;
use App\Services\DuplicateDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExactDuplicateCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentId)
    {
        $this->onQueue('duplicate');
    }

    public function handle(DuplicateDetectionService $duplicates, DocumentStatusService $statuses): void
    {
        $document = Document::query()->findOrFail($this->documentId);
        $result = $duplicates->exactFileMatch($document);

        if ($result['match']) {
            $duplicates->rememberCandidate($document, $result['match'], 100, $result['signals']);
            $statuses->transition($document, DocumentStatus::DuplicateExact, attributes: [
                'duplicate_score' => 100,
                'possible_duplicate_of' => $result['match']->id,
                'progress' => 100,
                'processing_completed_at' => now(),
            ]);
            $this->chained = [];
        }
    }
}
