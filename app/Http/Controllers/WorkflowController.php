<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\TransactionStatus;
use App\Models\AiExtraction;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Services\ApprovalService;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class WorkflowController extends Controller
{
    public function reviewQueue(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::TransactionReview), 403);

        $query = Transaction::query()
            ->forWorkspace($workspace)
            ->with(['vendor', 'document.latestExtraction', 'entries.account'])
            ->whereIn('status', [TransactionStatus::NeedsReview, TransactionStatus::Draft]);

        $sort = $request->string('sort')->toString() ?: 'oldest';

        if ($sort === 'duplicate') {
            $query->whereHas('document', fn ($q) => $q->whereJsonContains('flags', 'POSSIBLE_DUPLICATE'));
        }

        match ($sort) {
            'amount' => $query->orderByDesc('total_amount'),
            'confidence' => $query->orderBy(
                AiExtraction::query()
                    ->select('confidence')
                    ->whereColumn('ai_extractions.document_id', 'transactions.document_id')
                    ->latest()
                    ->limit(1)
            ),
            default => $query->orderBy('created_at'),
        };

        return Inertia::render('review/Index', [
            'transactions' => $query->paginate(20)->withQueryString(),
            'sort' => $sort,
        ]);
    }

    public function reviewShow(Request $request, Workspace $workspace, Transaction $transaction): Response
    {
        abort_unless($transaction->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::TransactionReview), 403);

        $transaction->load(['vendor', 'entries.account', 'document.latestExtraction', 'document.items', 'document.pages']);

        $nextId = Transaction::query()
            ->forWorkspace($workspace)
            ->whereIn('status', [TransactionStatus::NeedsReview, TransactionStatus::Draft])
            ->whereKeyNot($transaction->id)
            ->orderBy('created_at')
            ->value('id');

        return Inertia::render('review/Show', [
            'transaction' => $transaction,
            'document' => $transaction->document,
            'accounts' => $workspace->accounts()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'vendors' => $workspace->vendors()->orderBy('name')->get(['id', 'name']),
            'nextId' => $nextId,
        ]);
    }

    public function reviewStore(Request $request, Workspace $workspace, Transaction $transaction, ReviewService $reviews): RedirectResponse
    {
        abort_unless($transaction->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::TransactionReview), 403);

        $payload = $request->validate([
            'vendor_id' => ['nullable', 'uuid'],
            'transaction_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['nullable', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'service_charge' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'remember_mapping' => ['boolean'],
            'complete' => ['boolean'],
            'merchant_name' => ['nullable', 'string', 'max:160'],
            'document_number' => ['nullable', 'string', 'max:80'],
            'entries' => ['nullable', 'array'],
            'entries.*.account_id' => ['required_with:entries', 'uuid'],
            'entries.*.entry_type' => ['required_with:entries', 'in:DEBIT,CREDIT'],
            'entries.*.amount' => ['required_with:entries', 'numeric', 'min:0'],
            'entries.*.description' => ['nullable', 'string'],
        ]);

        try {
            $reviews->save(
                $workspace,
                $transaction->document,
                $request->user(),
                $payload,
                $request->boolean('complete'),
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return $request->boolean('complete')
            ? redirect()->route('workspaces.review', $workspace)->with('success', 'Review selesai.')
            : back()->with('success', 'Draft review disimpan.');
    }

    public function approvalQueue(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::TransactionApprove), 403);

        $transactions = Transaction::query()
            ->forWorkspace($workspace)
            ->with(['vendor', 'reviewer', 'entries.account', 'document'])
            ->where('status', TransactionStatus::WaitingApproval)
            ->latest('reviewed_at')
            ->paginate(20);

        return Inertia::render('approval/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function approve(Request $request, Workspace $workspace, Transaction $transaction, ApprovalService $approvals): RedirectResponse
    {
        abort_unless($transaction->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::TransactionApprove), 403);

        try {
            $approvals->approve($workspace, $transaction, $request->user(), $request->string('note')->toString() ?: null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Transaksi disetujui.');
    }

    public function reject(Request $request, Workspace $workspace, Transaction $transaction, ApprovalService $approvals): RedirectResponse
    {
        abort_unless($transaction->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::TransactionApprove), 403);

        $data = $request->validate(['note' => ['required', 'string', 'max:500']]);

        try {
            $approvals->reject($workspace, $transaction, $request->user(), $data['note']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Transaksi dikembalikan ke reviewer.');
    }
}
