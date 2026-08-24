<?php

namespace App\Http\Controllers;

use App\Models\AccountTemplate;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class WorkspaceController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('workspaces/Create', [
            'templates' => AccountTemplate::query()->where('is_active', true)->orderBy('sort_order')->get(['slug', 'name', 'description']),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(['slug', 'name', 'pages_per_month', 'max_members']),
        ]);
    }

    public function store(Request $request, WorkspaceService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'locale' => ['required', 'string', 'max:8'],
            'template' => ['nullable', 'string'],
            'require_separate_approver' => ['boolean'],
        ]);

        try {
            $workspace = $service->create($request->user(), $data);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        $request->session()->put('current_workspace_id', $workspace->id);

        return redirect()->route('workspaces.dashboard', $workspace)
            ->with('success', 'Workspace berhasil dibuat.');
    }

    public function settings(Workspace $workspace): Response
    {
        $this->authorize('update', $workspace);

        return Inertia::render('workspaces/Settings', [
            'workspaceModel' => $workspace->load('settings', 'settings.defaultCashAccount'),
            'accounts' => $workspace->accounts()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'locale' => ['required', 'string', 'max:8'],
            'require_approval' => ['boolean'],
            'require_separate_approver' => ['boolean'],
            'allow_approver_edit' => ['boolean'],
            'allow_export_unapproved' => ['boolean'],
            'uploader_can_view_amounts' => ['boolean'],
            'ai_processing_enabled' => ['boolean'],
            'duplicate_threshold' => ['numeric', 'min:0', 'max:100'],
            'default_cash_account_id' => ['nullable', 'uuid'],
        ]);

        $workspace->update([
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? null,
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
            'locale' => $data['locale'],
        ]);

        $workspace->settings()->update([
            'require_approval' => $request->boolean('require_approval'),
            'require_separate_approver' => $request->boolean('require_separate_approver'),
            'allow_approver_edit' => $request->boolean('allow_approver_edit'),
            'allow_export_unapproved' => $request->boolean('allow_export_unapproved'),
            'uploader_can_view_amounts' => $request->boolean('uploader_can_view_amounts'),
            'ai_processing_enabled' => $request->boolean('ai_processing_enabled'),
            'duplicate_threshold' => $data['duplicate_threshold'] ?? 75,
            'default_cash_account_id' => $data['default_cash_account_id'] ?? null,
        ]);

        return back()->with('success', 'Pengaturan workspace disimpan.');
    }
}
