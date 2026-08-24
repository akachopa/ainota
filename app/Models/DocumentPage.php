<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'document_id', 'document_version_id', 'page_number',
    'preview_path', 'ai_optimized_path', 'width', 'height',
])]
class DocumentPage extends Model
{
    use BelongsToWorkspace, HasUuids;

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
