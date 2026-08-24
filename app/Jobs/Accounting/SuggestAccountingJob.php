<?php

namespace App\Jobs\Accounting;

use App\Models\Document;
use App\Services\JournalDraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SuggestAccountingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentId)
    {
        $this->onQueue('accounting');
    }

    public function handle(JournalDraftService $journals): void
    {
        $document = Document::query()->with(['latestExtraction', 'workspace.settings'])->findOrFail($this->documentId);
        $data = $document->latestExtraction?->normalized_data ?? [];
        if ($data === []) {
            return;
        }

        $journals->createFromDocument($document, $data, $document->vendor_id);
    }
}
