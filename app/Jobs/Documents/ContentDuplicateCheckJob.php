<?php

namespace App\Jobs\Documents;

use App\Enums\DocumentFlag;
use App\Models\Document;
use App\Services\DuplicateDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ContentDuplicateCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentId)
    {
        $this->onQueue('duplicate');
    }

    public function handle(DuplicateDetectionService $duplicates): void
    {
        $document = Document::query()->findOrFail($this->documentId);
        $result = $duplicates->contentMatch($document);
        $threshold = (float) ($document->workspace?->settings?->duplicate_threshold ?? config('ainota.duplicate.thresholds.possible'));

        if ($result['match'] && $result['score'] >= $threshold) {
            $duplicates->rememberCandidate($document, $result['match'], $result['score'], $result['signals']);
            $flags = $document->flags ?? [];
            $flags[] = DocumentFlag::PossibleDuplicate->value;
            $document->update([
                'duplicate_score' => max((float) $document->duplicate_score, $result['score']),
                'possible_duplicate_of' => $document->possible_duplicate_of ?: $result['match']->id,
                'flags' => array_values(array_unique($flags)),
            ]);
        }
    }
}
