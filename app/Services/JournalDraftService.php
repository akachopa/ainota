<?php

namespace App\Services;

use App\Enums\DocumentFlag;
use App\Enums\EntryType;
use App\Enums\TransactionStatus;
use App\Models\Document;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Workspace;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class JournalDraftService
{
    public function __construct(private AccountRecommendationService $recommendations) {}

    public function createFromDocument(Document $document, array $normalized, ?string $vendorId): Transaction
    {
        $workspace = $document->workspace;
        $vendor = $vendorId ? $workspace->vendors()->whereKey($vendorId)->first() : $document->vendor;
        $recommendation = $this->recommendations->recommend($workspace, $vendor, $normalized);
        $total = Money::toFloat($normalized['grand_total'] ?? $document->grand_total);
        $tax = Money::toFloat($normalized['tax'] ?? 0);
        $subtotal = Money::toFloat($normalized['subtotal'] ?? $total);
        $discount = Money::toFloat($normalized['discount'] ?? 0);
        $service = Money::toFloat($normalized['service_charge'] ?? 0);

        return DB::transaction(function () use ($document, $workspace, $vendor, $normalized, $recommendation, $total, $tax, $subtotal, $discount, $service) {
            $transaction = Transaction::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'document_id' => $document->id,
                ],
                [
                    'vendor_id' => $vendor?->id,
                    'transaction_date' => $normalized['transaction_date'] ?? now()->toDateString(),
                    'reference_number' => $normalized['document_number'] ?? null,
                    'description' => $vendor?->name ?? $document->original_filename,
                    'currency' => $normalized['currency'] ?? $workspace->currency,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'service_charge' => $service,
                    'total_amount' => $total,
                    'payment_method' => $normalized['payment_method'] ?? null,
                    'status' => TransactionStatus::NeedsReview,
                    'created_by' => $document->uploaded_by,
                ],
            );

            $transaction->entries()->delete();
            $sort = 0;

            if ($recommendation['debit'] && $subtotal > 0) {
                $this->entry($workspace, $transaction, $recommendation['debit']->id, EntryType::Debit, $subtotal, $recommendation['debit']->name, $recommendation['source']->value, $sort++);
            }

            if ($tax > 0) {
                $ppn = $workspace->accounts()->where('code', '110501')->first();
                if ($ppn) {
                    $this->entry($workspace, $transaction, $ppn->id, EntryType::Debit, $tax, 'PPN Masukan', $recommendation['source']->value, $sort++);
                } elseif ($recommendation['debit']) {
                    $this->entry($workspace, $transaction, $recommendation['debit']->id, EntryType::Debit, $tax, 'Pajak', $recommendation['source']->value, $sort++);
                }
            }

            $creditAmount = $total > 0 ? $total : ($subtotal + $tax + $service - $discount);
            if ($recommendation['credit'] && $creditAmount > 0) {
                $this->entry($workspace, $transaction, $recommendation['credit']->id, EntryType::Credit, $creditAmount, $recommendation['credit']->name, $recommendation['source']->value, $sort++);
            }

            $this->recommendations->storeSuggestion(
                $workspace,
                $document->id,
                $recommendation['debit'],
                EntryType::Debit,
                $recommendation['source'],
                $recommendation['confidence'],
                $recommendation['reason'],
            );
            $this->recommendations->storeSuggestion(
                $workspace,
                $document->id,
                $recommendation['credit'],
                EntryType::Credit,
                $recommendation['source'],
                $recommendation['confidence'],
                $recommendation['reason'],
            );

            $flags = $document->flags ?? [];
            if (! $transaction->fresh(['entries'])?->isBalanced()) {
                $flags[] = DocumentFlag::UnbalancedJournal->value;
            }
            if (! $recommendation['debit'] || ! $recommendation['credit']) {
                $flags[] = DocumentFlag::AccountNotMapped->value;
            }
            $document->update(['flags' => array_values(array_unique($flags))]);

            return $transaction->fresh(['entries.account', 'vendor']);
        });
    }

    private function entry(
        Workspace $workspace,
        Transaction $transaction,
        string $accountId,
        EntryType $type,
        float $amount,
        string $description,
        string $source,
        int $sort,
    ): void {
        TransactionEntry::query()->create([
            'workspace_id' => $workspace->id,
            'transaction_id' => $transaction->id,
            'account_id' => $accountId,
            'entry_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'recommendation_source' => $source,
            'sort_order' => $sort,
        ]);
    }
}
