<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['workspace_id', 'period', 'ai_pages', 'provider_cost'])]
class WorkspaceUsage extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return ['provider_cost' => 'decimal:8'];
    }
}
