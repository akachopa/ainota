<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'document_id', 'version', 'storage_path', 'preview_path',
    'ai_optimized_path', 'sha256', 'is_active', 'created_by',
])]
class DocumentVersion extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
