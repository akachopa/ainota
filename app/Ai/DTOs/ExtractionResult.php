<?php

namespace App\Ai\DTOs;

final class ExtractionResult
{
    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>|null  $raw
     */
    public function __construct(
        public array $normalized,
        public ?array $raw,
        public string $provider,
        public string $model,
        public ?float $confidence,
        public ?int $inputTokens,
        public ?int $outputTokens,
        public ?float $estimatedCost,
        public ?int $latencyMs,
        public bool $success = true,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
