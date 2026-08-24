<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\Workspace;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $name,
            'normalized_name' => Money::normalizeName($name),
            'is_active' => true,
        ];
    }
}
