<?php

namespace App\Models;

use App\Enums\DuplicateResolution;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'document_id', 'matched_document_id', 'score', 'signals',
    'resolution', 'resolved_by', 'resolved_at',
])]
class DuplicateCandidate extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'signals' => 'array',
            'resolution' => DuplicateResolution::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function matchedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'matched_document_id');
    }
}
