<?php

namespace App\Jobs\Documents;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Services\DuplicateDetectionService;
use App\Services\VendorMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NormalizeExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentId)
    {
        $this->onQueue('accounting');
    }

    public function handle(VendorMatchingService $vendors, DuplicateDetectionService $duplicates): void
    {
        $document = Document::query()->with('latestExtraction')->findOrFail($this->documentId);
        $data = $document->latestExtraction?->normalized_data ?? [];
        if ($data === []) {
            return;
        }

        $match = $vendors->matchOrCreate($document->workspace, data_get($data, 'merchant.name'));
        $fingerprint = $duplicates->fingerprintFromExtraction($data);

        $document->items()->delete();
        foreach ($data['items'] ?? [] as $index => $item) {
            DocumentItem::query()->create([
                'workspace_id' => $document->workspace_id,
                'document_id' => $document->id,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit' => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'amount' => $item['amount'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $flags = $document->flags ?? [];
        if ($match['flag']) {
            $flags[] = $match['flag'];
        }

        $document->update([
            'document_type' => $data['document_type'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? null,
            'vendor_id' => $match['vendor']?->id,
            'grand_total' => $data['grand_total'] ?? null,
            'currency' => $data['currency'] ?? $document->workspace?->currency,
            'content_fingerprint' => $fingerprint,
            'flags' => array_values(array_unique($flags)),
        ]);
    }
}
