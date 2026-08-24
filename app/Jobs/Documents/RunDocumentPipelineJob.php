<?php

namespace App\Jobs\Documents;

use App\Jobs\Accounting\SuggestAccountingJob;
use App\Jobs\AI\ExtractDocumentWithAiJob;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class RunDocumentPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $documentId) {}

    public function handle(): void
    {
        $document = Document::query()->find($this->documentId);
        if (! $document) {
            return;
        }

        Bus::chain([
            new PrepareDocumentJob($this->documentId),
            new ExactDuplicateCheckJob($this->documentId),
            new PerceptualDuplicateCheckJob($this->documentId),
            new ExtractDocumentWithAiJob($this->documentId),
            new NormalizeExtractionJob($this->documentId),
            new ContentDuplicateCheckJob($this->documentId),
            new ValidateExtractionJob($this->documentId),
            new SuggestAccountingJob($this->documentId),
            new MarkNeedsReviewJob($this->documentId),
        ])->dispatch();
    }
}
