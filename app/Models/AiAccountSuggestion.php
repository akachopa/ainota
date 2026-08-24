<?php

namespace App\Models;

use App\Enums\EntryType;
use App\Enums\RecommendationSource;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id', 'document_id', 'account_id', 'entry_type', 'source',
    'confidence', 'reason', 'accepted',
])]
class AiAccountSuggestion extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'entry_type' => EntryType::class,
            'source' => RecommendationSource::class,
            'confidence' => 'decimal:4',
            'accepted' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
