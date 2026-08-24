<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'vendor_id', 'alias', 'normalized_alias'])]
class VendorAlias extends Model
{
    use BelongsToWorkspace, HasUuids;

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
