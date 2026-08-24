<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'document_id', 'document_version', 'provider', 'model',
    'schema_version', 'prompt_version', 'raw_response', 'normalized_data',
    'confidence', 'input_tokens', 'output_tokens', 'estimated_cost',
    'latency_ms', 'status', 'error_code', 'error_message', 'idempotency_key',
])]
class AiExtraction extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'normalized_data' => 'array',
            'confidence' => 'decimal:4',
            'estimated_cost' => 'decimal:8',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
