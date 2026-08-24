<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'workspace_id', 'document_id', 'ai_extraction_id', 'purpose', 'model',
    'input_tokens', 'output_tokens', 'estimated_cost', 'latency_ms', 'status', 'metadata',
])]
class AiRequest extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:8',
            'metadata' => 'array',
        ];
    }
}
