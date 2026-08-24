<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'transaction_date' => now()->toDateString(),
            'description' => 'Transaksi uji',
            'currency' => 'IDR',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge' => 0,
            'total_amount' => 100000,
            'status' => TransactionStatus::NeedsReview,
        ];
    }
}
