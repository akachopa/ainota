<?php

namespace App\Models;

use App\Enums\ExportType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'slug', 'name', 'type', 'is_system'])]
class ExportPreset extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => ExportType::class,
            'is_system' => 'boolean',
        ];
    }

    public function columns(): HasMany
    {
        return $this->hasMany(ExportPresetColumn::class)->orderBy('sort_order');
    }
}
