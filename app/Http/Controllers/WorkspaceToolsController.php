<?php

namespace App\Http\Controllers;

use App\Enums\ExportFormat;
use App\Enums\ExportType;
use App\Enums\Permission;
use App\Enums\TransactionStatus;
use App\Models\Export;
use App\Models\PaymentAccountMapping;
use App\Models\Transaction;
use App\Models\UploadLink;
use App\Models\UsageLedger;
use App\Models\Workspace;
use App\Services\ExportService;
use App\Services\UsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceToolsController extends Controller
{
    public function transactions(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::DocumentViewAll)
            || $request->user()->canIn($workspace, Permission::TransactionReview)
            || $request->user()->canIn($workspace, Permission::TransactionApprove), 403);

        $query = Transaction::query()->forWorkspace($workspace)->with(['vendor', 'document']);
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return Inertia::render('transactions/Index', [
            'transactions' => $query->latest('transaction_date')->paginate(20)->withQueryString(),
            'statuses' => collect(TransactionStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function exportIndex(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::TransactionExport), 403);

        return Inertia::render('exports/Index', [
            'exports' => Export::query()->forWorkspace($workspace)->latest()->paginate(20),
            'allowUnapproved' => (bool) $workspace->settings?->allow_export_unapproved,
        ]);
    }

    public function storeExport(Request $request, Workspace $workspace, ExportService $exports): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::TransactionExport), 403);
        $data = $request->validate([
            'type' => ['required', Rule::enum(ExportType::class)],
            'format' => ['required', Rule::enum(ExportFormat::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $export = $exports->request(
            $workspace,
            $request->user(),
            ExportType::from($data['type']),
            ExportFormat::from($data['format']),
            $request->only(['from', 'to', 'status']),
        );

        return back()->with('success', $export->status->value === 'READY' ? 'Export siap diunduh.' : 'Export sedang diproses.');
    }

    public function downloadExport(Request $request, Workspace $workspace, Export $export): StreamedResponse
    {
        abort_unless($export->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::TransactionExport), 403);
        abort_unless($export->storage_path && Storage::disk($export->storage_disk)->exists($export->storage_path), 404);

        $export->update(['downloaded_at' => now()]);

        return Storage::disk($export->storage_disk)->download(
            $export->storage_path,
            $export->type->value.'.'.$export->format->value,
        );
    }

    public function paymentMappings(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::CoaManage), 403);

        return Inertia::render('payments/Index', [
            'mappings' => PaymentAccountMapping::query()->forWorkspace($workspace)->with('account')->get(),
            'accounts' => $workspace->accounts()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function storePaymentMapping(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::CoaManage), 403);
        $data = $request->validate([
            'keyword' => ['required', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:80'],
            'account_id' => ['required', 'uuid'],
            'is_default' => ['boolean'],
        ]);
        if ($request->boolean('is_default')) {
            PaymentAccountMapping::query()->forWorkspace($workspace)->update(['is_default' => false]);
        }
        PaymentAccountMapping::query()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'keyword' => strtolower($data['keyword'])],
            [...$data, 'workspace_id' => $workspace->id, 'keyword' => strtolower($data['keyword'])],
        );

        return back()->with('success', 'Mapping pembayaran disimpan.');
    }

    public function uploadLinks(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::UploadLinkManage), 403);

        return Inertia::render('upload-links/Index', [
            'links' => UploadLink::query()->forWorkspace($workspace)->latest()->get(),
        ]);
    }

    public function storeUploadLink(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::UploadLinkManage), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'expires_at' => ['nullable', 'date'],
            'max_uploads' => ['nullable', 'integer', 'min:1'],
            'require_uploader_name' => ['boolean'],
            'pin' => ['nullable', 'string', 'max:12'],
        ]);
        $token = Str::random(40);
        UploadLink::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'public_token' => $token,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $data['expires_at'] ?? now()->addDays((int) config('ainota.upload_links.default_expires_days')),
            'max_uploads' => $data['max_uploads'] ?? config('ainota.upload_links.default_max_uploads'),
            'require_uploader_name' => $request->boolean('require_uploader_name', true),
            'pin_hash' => filled($data['pin'] ?? null) ? Hash::make($data['pin']) : null,
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        return back()->with('success', 'Link unggah dibuat.');
    }

    public function usageIndex(Request $request, Workspace $workspace, UsageService $usage): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::BillingManage)
            || $request->user()->canIn($workspace, Permission::WorkspaceManage)
            || $workspace->memberFor($request->user())?->role->value === 'owner', 403);

        $plan = $usage->currentPlan($workspace);

        return Inertia::render('usage/Index', [
            'plan' => $plan,
            'used' => $usage->pagesUsedThisMonth($workspace),
            'remaining' => $usage->remainingPages($workspace),
            'ledgers' => $workspace->subscription
                ? UsageLedger::query()->forWorkspace($workspace)->latest()->limit(30)->get()
                : UsageLedger::query()->forWorkspace($workspace)->latest()->limit(30)->get(),
        ]);
    }
}
