<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['export_preset_id', 'header', 'source_key', 'sort_order'])]
class ExportPresetColumn extends Model
{
    use HasUuids;

    public function preset(): BelongsTo
    {
        return $this->belongsTo(ExportPreset::class, 'export_preset_id');
    }
}
