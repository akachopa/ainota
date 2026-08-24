<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['account_template_id', 'code', 'name', 'type', 'normal_balance', 'parent_code', 'description', 'sort_order'])]
class AccountTemplateItem extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AccountTemplate::class, 'account_template_id');
    }
}
