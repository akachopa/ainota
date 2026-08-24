<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'slug' => $name,
            'name' => ucfirst($name),
            'max_workspaces' => 1,
            'max_members' => 3,
            'pages_per_month' => 50,
            'features' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
