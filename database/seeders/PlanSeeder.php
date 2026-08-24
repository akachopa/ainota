<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'free', 'name' => 'Free', 'max_workspaces' => 1, 'max_members' => 3, 'pages_per_month' => 50, 'sort_order' => 1],
            ['slug' => 'starter', 'name' => 'Starter', 'max_workspaces' => 5, 'max_members' => 10, 'pages_per_month' => 500, 'sort_order' => 2],
            ['slug' => 'professional', 'name' => 'Professional', 'max_workspaces' => 20, 'max_members' => 30, 'pages_per_month' => 3000, 'sort_order' => 3],
            ['slug' => 'accounting-firm', 'name' => 'Accounting Firm', 'max_workspaces' => 100, 'max_members' => 100, 'pages_per_month' => 20000, 'sort_order' => 4],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
