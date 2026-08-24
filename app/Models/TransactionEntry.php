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
    'workspace_id', 'transaction_id', 'account_id', 'entry_type', 'amount',
    'description', 'recommendation_source', 'sort_order',
])]
class TransactionEntry extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'entry_type' => EntryType::class,
            'amount' => 'decimal:2',
            'recommendation_source' => RecommendationSource::class,
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
