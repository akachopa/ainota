<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $type = AccountType::Expense;

        return [
            'workspace_id' => Workspace::factory(),
            'code' => (string) fake()->unique()->numerify('6#####'),
            'name' => fake()->words(2, true),
            'type' => $type,
            'normal_balance' => $type->defaultNormalBalance(),
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 0,
        ];
    }
}
