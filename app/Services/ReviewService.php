<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\EntryType;
use App\Enums\RecommendationSource;
use App\Enums\TransactionStatus;
use App\Models\AiFeedback;
use App\Models\Document;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReviewService
{
    public function __construct(
        private DocumentStatusService $statuses,
        private AccountRecommendationService $recommendations,
        private ActivityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(Workspace $workspace, Document $document, User $reviewer, array $payload, bool $complete = false): Transaction
    {
        $transaction = $document->transaction;
        if (! $transaction) {
            throw new RuntimeException('Draft transaksi belum tersedia.');
        }

        return DB::transaction(function () use ($workspace, $document, $transaction, $reviewer, $payload, $complete) {
            $before = $transaction->toArray();

            $transaction->update([
                'vendor_id' => $payload['vendor_id'] ?? $transaction->vendor_id,
                'transaction_date' => $payload['transaction_date'] ?? $transaction->transaction_date,
                'reference_number' => $payload['reference_number'] ?? $transaction->reference_number,
                'description' => $payload['description'] ?? $transaction->description,
                'subtotal' => $payload['subtotal'] ?? $transaction->subtotal,
                'discount_amount' => $payload['discount_amount'] ?? $transaction->discount_amount,
                'tax_amount' => $payload['tax_amount'] ?? $transaction->tax_amount,
                'service_charge' => $payload['service_charge'] ?? $transaction->service_charge,
                'total_amount' => $payload['total_amount'] ?? $transaction->total_amount,
                'payment_method' => $payload['payment_method'] ?? $transaction->payment_method,
                'review_note' => $payload['note'] ?? $transaction->review_note,
            ]);

            if (isset($payload['merchant_name']) || isset($payload['document_number'])) {
                $extraction = $document->latestExtraction;
                if ($extraction) {
                    $data = $extraction->normalized_data ?? [];
                    if (isset($payload['merchant_name'])) {
                        $data['merchant']['name'] = $payload['merchant_name'];
                    }
                    if (array_key_exists('document_number', $payload)) {
                        $data['document_number'] = $payload['document_number'];
                    }
                    $extraction->update(['normalized_data' => $data]);
                }
            }

            if (isset($payload['entries']) && is_array($payload['entries'])) {
                $transaction->entries()->delete();
                foreach ($payload['entries'] as $index => $entry) {
                    TransactionEntry::query()->create([
                        'workspace_id' => $workspace->id,
                        'transaction_id' => $transaction->id,
                        'account_id' => $entry['account_id'],
                        'entry_type' => $entry['entry_type'],
                        'amount' => $entry['amount'],
                        'description' => $entry['description'] ?? null,
                        'recommendation_source' => RecommendationSource::Manual,
                        'sort_order' => $index,
                    ]);
                }
            }

            $transaction = $transaction->fresh(['entries', 'vendor']);

            if (! empty($payload['remember_mapping']) && $transaction->vendor_id) {
                $debit = $transaction->entries->firstWhere('entry_type', EntryType::Debit);
                $credit = $transaction->entries->firstWhere('entry_type', EntryType::Credit);
                if ($debit && $transaction->vendor) {
                    $this->recommendations->remember(
                        $workspace,
                        $transaction->vendor,
                        $debit->account,
                        $credit?->account,
                    );
                }
            }

            $this->captureFeedback($workspace, $document, $reviewer, $before, $transaction->toArray());

            Review::query()->create([
                'workspace_id' => $workspace->id,
                'document_id' => $document->id,
                'transaction_id' => $transaction->id,
                'reviewer_id' => $reviewer->id,
                'status' => $complete ? 'completed' : 'draft',
                'note' => $payload['note'] ?? null,
                'remember_vendor_mapping' => (bool) ($payload['remember_mapping'] ?? false),
                'completed_at' => $complete ? now() : null,
            ]);

            $this->logger->log('extraction.edited', $workspace, $reviewer, $document, $before, $transaction->toArray());

            if ($complete) {
                if (! $transaction->fresh(['entries'])->isBalanced()) {
                    throw new RuntimeException('Jurnal belum seimbang. Debit harus sama dengan kredit.');
                }

                $transaction->update([
                    'status' => $workspace->settings?->require_approval
                        ? TransactionStatus::WaitingApproval
                        : TransactionStatus::Approved,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                    'approved_by' => $workspace->settings?->require_approval ? null : $reviewer->id,
                    'approved_at' => $workspace->settings?->require_approval ? null : now(),
                ]);

                $this->statuses->transition(
                    $document,
                    $workspace->settings?->require_approval ? DocumentStatus::WaitingApproval : DocumentStatus::Approved,
                    $reviewer,
                    progress: $workspace->settings?->require_approval ? 90 : 100,
                );

                $this->logger->log('review.completed', $workspace, $reviewer, $transaction);
            }

            return $transaction->fresh(['entries.account', 'vendor']);
        });
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function captureFeedback(Workspace $workspace, Document $document, User $user, array $before, array $after): void
    {
        foreach (['vendor_id', 'description', 'total_amount'] as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                AiFeedback::query()->create([
                    'workspace_id' => $workspace->id,
                    'document_id' => $document->id,
                    'field' => $field,
                    'predicted_value' => (string) ($before[$field] ?? ''),
                    'final_value' => (string) ($after[$field] ?? ''),
                    'vendor_id' => $after['vendor_id'] ?? null,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}
