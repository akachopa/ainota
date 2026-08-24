<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\TransactionStatus;
use App\Models\Approval;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\GenericWorkspaceNotification;
use RuntimeException;

class ApprovalService
{
    public function __construct(
        private DocumentStatusService $statuses,
        private ActivityLogger $logger,
    ) {}

    public function approve(Workspace $workspace, Transaction $transaction, User $approver, ?string $note = null): Transaction
    {
        if ($transaction->status !== TransactionStatus::WaitingApproval) {
            throw new RuntimeException('Transaksi belum siap disetujui.');
        }

        $transaction->load('entries.account');

        if (! $transaction->isBalanced()) {
            throw new RuntimeException('Jurnal belum seimbang.');
        }

        if ($transaction->entries->contains(fn ($entry) => ! $entry->account?->is_active)) {
            throw new RuntimeException('Ada akun yang tidak aktif.');
        }

        if ($workspace->settings?->require_separate_approver && $transaction->reviewed_by === $approver->id) {
            throw new RuntimeException('Pemisahan tugas aktif: reviewer tidak boleh menyetujui transaksi yang sama.');
        }

        $transaction->update([
            'status' => TransactionStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_note' => $note,
        ]);

        Approval::query()->create([
            'workspace_id' => $workspace->id,
            'transaction_id' => $transaction->id,
            'approver_id' => $approver->id,
            'decision' => 'approved',
            'note' => $note,
            'decided_at' => now(),
        ]);

        if ($transaction->document) {
            $this->statuses->transition($transaction->document, DocumentStatus::Approved, $approver, progress: 100);
        }

        $this->logger->log('transaction.approved', $workspace, $approver, $transaction);

        return $transaction->fresh();
    }

    public function reject(Workspace $workspace, Transaction $transaction, User $approver, string $note): Transaction
    {
        $transaction->update([
            'status' => TransactionStatus::NeedsReview,
            'approval_note' => $note,
        ]);

        Approval::query()->create([
            'workspace_id' => $workspace->id,
            'transaction_id' => $transaction->id,
            'approver_id' => $approver->id,
            'decision' => 'rejected',
            'note' => $note,
            'decided_at' => now(),
        ]);

        if ($transaction->document) {
            $this->statuses->transition($transaction->document, DocumentStatus::NeedsReview, $approver, progress: 70);
        }

        $this->logger->log('transaction.rejected', $workspace, $approver, $transaction, metadata: ['note' => $note]);

        $reviewer = $transaction->reviewer;
        $reviewer?->notify(new GenericWorkspaceNotification(
            $workspace,
            'Transaksi ditolak',
            'Transaksi '.$transaction->reference_number.' dikembalikan ke antrian review.',
        ));

        return $transaction->fresh();
    }
}
