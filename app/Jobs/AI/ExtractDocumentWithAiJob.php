<?php

namespace App\Jobs\AI;

use App\Ai\Contracts\DocumentExtractorInterface;
use App\Enums\DocumentStatus;
use App\Enums\FailureCode;
use App\Models\AiExtraction;
use App\Models\AiRequest;
use App\Models\Document;
use App\Services\DocumentStatusService;
use App\Services\UsageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExtractDocumentWithAiJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public int $timeout = 120;

    public function __construct(public string $documentId)
    {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        $document = Document::query()->find($this->documentId);

        return 'ai-extract:'.($document?->workspace_id ?? 'x').':'.$this->documentId.':'.($document?->current_version ?? 1);
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('ai')];
    }

    public function handle(
        DocumentExtractorInterface $extractor,
        DocumentStatusService $statuses,
        UsageService $usage,
    ): void {
        $document = Document::query()->with('workspace')->findOrFail($this->documentId);

        if (in_array($document->status, [DocumentStatus::DuplicateExact, DocumentStatus::Failed], true)) {
            $this->chained = [];

            return;
        }

        $idempotency = 'ai-extract:'.$document->workspace_id.':'.$document->id.':'.$document->current_version;
        $existing = AiExtraction::query()->where('idempotency_key', $idempotency)->where('status', 'success')->first();
        if ($existing) {
            return;
        }

        if (! $document->workspace?->settings?->ai_processing_enabled) {
            $statuses->transition($document, DocumentStatus::NeedsReview, progress: 80);

            return;
        }

        $usage->assertCanProcessPages($document->workspace, $document->page_count ?: 1);
        $statuses->transition($document, DocumentStatus::AiProcessing, progress: 55);

        $result = $extractor->extract($document);

        $extraction = AiExtraction::query()->updateOrCreate(
            ['idempotency_key' => $idempotency],
            [
                'workspace_id' => $document->workspace_id,
                'document_id' => $document->id,
                'document_version' => $document->current_version,
                'provider' => $result->provider,
                'model' => $result->model,
                'schema_version' => config('ainota.ai.schema_version'),
                'prompt_version' => config('ainota.ai.prompt_version'),
                'raw_response' => $result->raw,
                'normalized_data' => $result->normalized,
                'confidence' => $result->confidence,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'estimated_cost' => $result->estimatedCost,
                'latency_ms' => $result->latencyMs,
                'status' => $result->success ? 'success' : 'failed',
                'error_code' => $result->errorCode,
                'error_message' => $result->errorMessage,
            ],
        );

        AiRequest::query()->create([
            'workspace_id' => $document->workspace_id,
            'document_id' => $document->id,
            'ai_extraction_id' => $extraction->id,
            'purpose' => 'extraction',
            'model' => $result->model,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'estimated_cost' => $result->estimatedCost,
            'latency_ms' => $result->latencyMs,
            'status' => $result->success ? 'success' : 'failed',
        ]);

        if (! $result->success) {
            $retryable = str_contains(strtolower((string) $result->errorMessage), '429')
                || str_contains(strtolower((string) $result->errorMessage), 'timeout')
                || str_contains(strtolower((string) $result->errorMessage), '5');

            if ($retryable && $this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);

                return;
            }

            $statuses->transition($document, DocumentStatus::Failed, attributes: [
                'failure_code' => FailureCode::AiProviderError,
                'failure_message' => $result->errorMessage,
                'progress' => 100,
            ]);
            $this->chained = [];

            return;
        }

        $usage->recordAiPages(
            $document->workspace,
            $document->page_count ?: 1,
            AiExtraction::class,
            $extraction->id,
            $result->estimatedCost,
            charge: true,
        );

        $statuses->transition($document, DocumentStatus::AiProcessed, progress: 70);
    }

    public function failed(?Throwable $exception): void
    {
        $document = Document::query()->find($this->documentId);
        if ($document && $document->status === DocumentStatus::AiProcessing) {
            app(DocumentStatusService::class)->transition($document, DocumentStatus::Failed, attributes: [
                'failure_code' => FailureCode::AiProviderError,
                'failure_message' => $exception?->getMessage(),
            ]);
        }
    }
}
