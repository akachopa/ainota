<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'workspace_id', 'document_id', 'field', 'predicted_value', 'final_value',
    'vendor_id', 'context', 'created_by',
])]
class AiFeedback extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return ['context' => 'array'];
    }
}
