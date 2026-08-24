<?php

namespace App\Ai\OpenAI;

use App\Ai\Contracts\DocumentExtractorInterface;
use App\Ai\DTOs\ExtractionResult;
use App\Ai\Schemas\ReceiptExtractionSchema;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class OpenAiDocumentExtractor implements DocumentExtractorInterface
{
    public function extract(Document $document): ExtractionResult
    {
        $model = (string) config('ainota.ai.extraction_model');
        $started = microtime(true);

        try {
            $image = $this->imagePayload($document);

            $response = OpenAI::chat()->create([
                'model' => $model,
                'temperature' => 0,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'receipt_extraction',
                        'strict' => true,
                        'schema' => ReceiptExtractionSchema::jsonSchema(),
                    ],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt($document),
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Ekstrak data transaksi dari dokumen ini. Jika field tidak terbaca, kembalikan null. Jangan menebak.',
                            ],
                            $image,
                        ],
                    ],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            $normalized = json_decode($content, true);
            if (! is_array($normalized)) {
                $normalized = [];
            }

            $input = $response->usage->promptTokens ?? null;
            $output = $response->usage->completionTokens ?? null;
            $prices = config('ainota.ai.model_prices.'.$model, config('ainota.ai.model_prices.gpt-5-nano'));
            $cost = null;
            if ($input !== null && $output !== null && is_array($prices)) {
                $cost = ($input * (float) $prices['input']) + ($output * (float) $prices['output']);
            }

            return new ExtractionResult(
                normalized: $normalized,
                raw: json_decode(json_encode($response->toArray()), true),
                provider: 'openai',
                model: $model,
                confidence: isset($normalized['confidence']['overall']) ? (float) $normalized['confidence']['overall'] : null,
                inputTokens: $input,
                outputTokens: $output,
                estimatedCost: $cost,
                latencyMs: (int) round((microtime(true) - $started) * 1000),
            );
        } catch (Throwable $e) {
            return new ExtractionResult(
                normalized: [],
                raw: null,
                provider: 'openai',
                model: $model,
                confidence: null,
                inputTokens: null,
                outputTokens: null,
                estimatedCost: null,
                latencyMs: (int) round((microtime(true) - $started) * 1000),
                success: false,
                errorCode: 'AI_PROVIDER_ERROR',
                errorMessage: $e->getMessage(),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function imagePayload(Document $document): array
    {
        $path = $document->currentVersionRecord?->ai_optimized_path ?? $document->storage_path;
        $binary = Storage::disk($document->storage_disk)->get($path);
        $mime = $document->mime_type === 'application/pdf' ? 'image/jpeg' : $document->mime_type;

        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => 'data:'.$mime.';base64,'.base64_encode((string) $binary),
            ],
        ];
    }

    private function systemPrompt(Document $document): string
    {
        $locale = $document->workspace?->locale ?? 'id';

        return <<<PROMPT
Anda mengekstrak data nota/invoice untuk workspace berbahasa {$locale}.
Hanya gunakan informasi yang terlihat di dokumen.
Jika tidak yakin, isi null.
Jangan membuat fakta, akun akuntansi, atau asumsi.
PROMPT;
    }
}
