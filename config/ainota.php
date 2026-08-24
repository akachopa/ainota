<?php

return [
    'documents' => [
        'disk' => env('DOCUMENT_DISK', env('FILESYSTEM_DISK', 'local')),
        'max_size_kb' => (int) env('DOCUMENT_MAX_SIZE_KB', 10240),
        'max_pdf_pages' => (int) env('DOCUMENT_MAX_PDF_PAGES', 5),
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        'stale_processing_minutes' => (int) env('DOCUMENT_STALE_MINUTES', 20),
    ],

    'ai' => [
        'extraction_model' => env('AI_EXTRACTION_MODEL', 'gpt-5-nano'),
        'fallback_model' => env('AI_FALLBACK_MODEL'),
        'schema_version' => env('AI_EXTRACTION_SCHEMA_VERSION', 'v1'),
        'prompt_version' => env('AI_EXTRACTION_PROMPT_VERSION', 'receipt_extraction_v1'),
        'timeout' => (int) env('OPENAI_REQUEST_TIMEOUT', 120),
        'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 30),
        'model_prices' => [
            'gpt-5-nano' => [
                'input' => (float) env('AI_PRICE_GPT5_NANO_INPUT', 0.00000005),
                'output' => (float) env('AI_PRICE_GPT5_NANO_OUTPUT', 0.0000004),
            ],
        ],
    ],

    'duplicate' => [
        'weights' => [
            'exact_sha256' => 100,
            'invoice_number_exact' => 35,
            'amount_exact' => 20,
            'transaction_date_exact' => 15,
            'vendor_high_similarity' => 15,
            'perceptual_image' => 25,
            'ocr_content' => 20,
        ],
        'thresholds' => [
            'exact' => 100,
            'very_high' => 90,
            'possible' => 75,
        ],
        'perceptual_hamming_threshold' => 8,
        'amount_tolerance' => 1.00,
        'vendor_similarity_threshold' => 80,
    ],

    'validation' => [
        'amount_tolerance' => 1.00,
        'future_date_days' => 3,
        'low_confidence' => 0.75,
        'high_confidence' => 0.90,
    ],

    'export' => [
        'async_row_threshold' => (int) env('EXPORT_ASYNC_THRESHOLD', 500),
        'retention_days' => (int) env('EXPORT_RETENTION_DAYS', 30),
    ],

    'usage' => [
        'page_unit' => 1,
        'warn_at_percent' => 80,
    ],

    'invitations' => [
        'expires_days' => 7,
    ],

    'upload_links' => [
        'default_expires_days' => 14,
        'default_max_uploads' => 100,
    ],
];
