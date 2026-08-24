<?php

namespace App\Services;

use App\Enums\DocumentFlag;
use App\Models\Vendor;
use App\Models\VendorAlias;
use App\Models\Workspace;
use App\Support\Money;

class VendorMatchingService
{
    /**
     * @return array{vendor: ?Vendor, flag: ?string}
     */
    public function matchOrCreate(Workspace $workspace, ?string $name, bool $create = true): array
    {
        if (blank($name)) {
            return ['vendor' => null, 'flag' => DocumentFlag::UnknownVendor->value];
        }

        $normalized = Money::normalizeName($name);

        $vendor = Vendor::query()
            ->forWorkspace($workspace)
            ->where('normalized_name', $normalized)
            ->first();

        if (! $vendor) {
            $alias = VendorAlias::query()
                ->forWorkspace($workspace)
                ->where('normalized_alias', $normalized)
                ->first();
            $vendor = $alias?->vendor;
        }

        if (! $vendor) {
            $candidates = Vendor::query()->forWorkspace($workspace)->where('is_active', true)->get();
            foreach ($candidates as $candidate) {
                similar_text($normalized, $candidate->normalized_name, $percent);
                if ($percent >= (float) config('ainota.duplicate.vendor_similarity_threshold')) {
                    $vendor = $candidate;
                    break;
                }
            }
        }

        if ($vendor) {
            return ['vendor' => $vendor, 'flag' => null];
        }

        if (! $create) {
            return ['vendor' => null, 'flag' => DocumentFlag::UnknownVendor->value];
        }

        $vendor = Vendor::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'normalized_name' => $normalized,
            'is_active' => true,
        ]);

        return ['vendor' => $vendor, 'flag' => DocumentFlag::UnknownVendor->value];
    }
}
