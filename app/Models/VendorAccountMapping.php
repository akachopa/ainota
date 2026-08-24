<?php

namespace App\Models;

use App\Enums\RecommendationSource;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'vendor_id', 'debit_account_id', 'credit_account_id', 'source'])]
class VendorAccountMapping extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return ['source' => RecommendationSource::class];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }
}
