<?php

namespace App\Ai\Schemas;

class ReceiptExtractionSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'document_type' => ['type' => ['string', 'null']],
                'merchant' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'name' => ['type' => ['string', 'null']],
                        'tax_id' => ['type' => ['string', 'null']],
                        'address' => ['type' => ['string', 'null']],
                    ],
                    'required' => ['name', 'tax_id', 'address'],
                ],
                'document_number' => ['type' => ['string', 'null']],
                'transaction_date' => ['type' => ['string', 'null']],
                'currency' => ['type' => ['string', 'null']],
                'subtotal' => ['type' => ['number', 'null']],
                'discount' => ['type' => ['number', 'null']],
                'tax' => ['type' => ['number', 'null']],
                'service_charge' => ['type' => ['number', 'null']],
                'grand_total' => ['type' => ['number', 'null']],
                'payment_method' => ['type' => ['string', 'null']],
                'bank_hint' => ['type' => ['string', 'null']],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'description' => ['type' => ['string', 'null']],
                            'quantity' => ['type' => ['number', 'null']],
                            'unit' => ['type' => ['string', 'null']],
                            'unit_price' => ['type' => ['number', 'null']],
                            'amount' => ['type' => ['number', 'null']],
                        ],
                        'required' => ['description', 'quantity', 'unit', 'unit_price', 'amount'],
                    ],
                ],
                'notes' => ['type' => ['string', 'null']],
                'confidence' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'overall' => ['type' => ['number', 'null']],
                        'merchant' => ['type' => ['number', 'null']],
                        'date' => ['type' => ['number', 'null']],
                        'total' => ['type' => ['number', 'null']],
                        'items' => ['type' => ['number', 'null']],
                    ],
                    'required' => ['overall', 'merchant', 'date', 'total', 'items'],
                ],
            ],
            'required' => [
                'document_type',
                'merchant',
                'document_number',
                'transaction_date',
                'currency',
                'subtotal',
                'discount',
                'tax',
                'service_charge',
                'grand_total',
                'payment_method',
                'bank_hint',
                'items',
                'notes',
                'confidence',
            ],
        ];
    }
}
