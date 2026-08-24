<?php

namespace Database\Factories;

use App\Enums\WorkspaceStatus;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Workspace::uniqueSlug($name),
            'legal_name' => $name,
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'status' => WorkspaceStatus::Active,
            'plan_id' => Plan::query()->where('slug', 'free')->value('id') ?? Plan::factory(),
        ];
    }
}
