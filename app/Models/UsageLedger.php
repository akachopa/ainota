<?php

namespace App\Models;

use App\Enums\UsageType;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'workspace_id', 'subscription_id', 'usage_type', 'quantity',
    'reference_type', 'reference_id', 'provider_cost', 'metadata',
])]
class UsageLedger extends Model
{
    use BelongsToWorkspace, HasUuids;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'usage_type' => UsageType::class,
            'provider_cost' => 'decimal:8',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
