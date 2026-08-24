<?php

namespace App\Ai;

use App\Ai\Contracts\DocumentExtractorInterface;
use App\Ai\DTOs\ExtractionResult;
use App\Models\Document;

class FakeDocumentExtractor implements DocumentExtractorInterface
{
    public function extract(Document $document): ExtractionResult
    {
        $normalized = [
            'document_type' => 'receipt',
            'merchant' => [
                'name' => 'SPBU Pertamina',
                'tax_id' => null,
                'address' => null,
            ],
            'document_number' => '018271',
            'transaction_date' => now()->toDateString(),
            'currency' => $document->workspace?->currency ?? 'IDR',
            'subtotal' => 350000,
            'discount' => 0,
            'tax' => 0,
            'service_charge' => 0,
            'grand_total' => 350000,
            'payment_method' => 'bank_transfer',
            'bank_hint' => 'BCA',
            'items' => [
                [
                    'description' => 'Pertamax',
                    'quantity' => 25.18,
                    'unit' => 'liter',
                    'unit_price' => 13900,
                    'amount' => 350000,
                ],
            ],
            'notes' => null,
            'confidence' => [
                'overall' => 0.96,
                'merchant' => 0.98,
                'date' => 0.94,
                'total' => 0.99,
                'items' => 0.90,
            ],
        ];

        return new ExtractionResult(
            normalized: $normalized,
            raw: ['stub' => true, 'filename' => $document->original_filename],
            provider: 'stub',
            model: 'stub-extractor',
            confidence: 0.96,
            inputTokens: 0,
            outputTokens: 0,
            estimatedCost: 0,
            latencyMs: 5,
        );
    }
}
