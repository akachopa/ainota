<?php

use App\Enums\EntryType;
use App\Enums\ExportFormat;
use App\Enums\ExportType;
use App\Enums\TransactionStatus;
use App\Enums\WorkspaceRole;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\ExportService;
use App\Services\ExtractionValidationService;

it('flags total mismatch during validation', function () {
    $flags = app(ExtractionValidationService::class)->flags([
        'subtotal' => 100000,
        'discount' => 0,
        'tax' => 0,
        'service_charge' => 0,
        'grand_total' => 150000,
        'confidence' => ['overall' => 0.9, 'total' => 0.9],
        'items' => [],
    ]);

    expect($flags)->toContain('TOTAL_MISMATCH');
});

it('rejects approval when journal is unbalanced', function () {
    $approver = User::factory()->create();
    $workspace = createWorkspaceFor($approver, WorkspaceRole::Approver);
    $account = Account::query()->forWorkspace($workspace)->where('code', '610104')->first();
    $bank = Account::query()->forWorkspace($workspace)->where('code', '110201')->first();

    $transaction = Transaction::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => TransactionStatus::WaitingApproval,
        'total_amount' => 100000,
    ]);

    TransactionEntry::query()->create([
        'workspace_id' => $workspace->id,
        'transaction_id' => $transaction->id,
        'account_id' => $account->id,
        'entry_type' => EntryType::Debit,
        'amount' => 100000,
        'sort_order' => 0,
    ]);
    TransactionEntry::query()->create([
        'workspace_id' => $workspace->id,
        'transaction_id' => $transaction->id,
        'account_id' => $bank->id,
        'entry_type' => EntryType::Credit,
        'amount' => 90000,
        'sort_order' => 1,
    ]);

    expect(fn () => app(ApprovalService::class)->approve($workspace, $transaction->fresh('entries'), $approver))
        ->toThrow(RuntimeException::class);
});

it('approves a balanced journal', function () {
    $approver = User::factory()->create();
    $workspace = createWorkspaceFor($approver, WorkspaceRole::Approver);
    $account = Account::query()->forWorkspace($workspace)->where('code', '610104')->first();
    $bank = Account::query()->forWorkspace($workspace)->where('code', '110201')->first();

    $transaction = Transaction::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => TransactionStatus::WaitingApproval,
        'total_amount' => 100000,
        'reviewed_by' => User::factory(),
    ]);

    TransactionEntry::query()->create([
        'workspace_id' => $workspace->id,
        'transaction_id' => $transaction->id,
        'account_id' => $account->id,
        'entry_type' => EntryType::Debit,
        'amount' => 100000,
        'sort_order' => 0,
    ]);
    TransactionEntry::query()->create([
        'workspace_id' => $workspace->id,
        'transaction_id' => $transaction->id,
        'account_id' => $bank->id,
        'entry_type' => EntryType::Credit,
        'amount' => 100000,
        'sort_order' => 1,
    ]);

    $approved = app(ApprovalService::class)->approve($workspace, $transaction->fresh('entries.account'), $approver);

    expect($approved->status)->toBe(TransactionStatus::Approved);
});

it('exports only approved transactions by default', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    Transaction::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => TransactionStatus::Approved,
        'total_amount' => 10,
    ]);
    Transaction::factory()->create([
        'workspace_id' => $workspace->id,
        'status' => TransactionStatus::NeedsReview,
        'total_amount' => 99,
    ]);

    $export = app(ExportService::class)->request($workspace, $owner, ExportType::TransactionTable, ExportFormat::Csv);
    $rows = app(ExportService::class)->buildRows($export->fresh());

    expect($rows)->toHaveCount(2);
});
