<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'parent_id', 'code', 'name', 'type', 'normal_balance',
    'description', 'is_active', 'is_system', 'sort_order',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function label(): string
    {
        return $this->code.' '.$this->name;
    }
}
