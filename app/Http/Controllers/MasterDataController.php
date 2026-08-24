<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Models\Account;
use App\Models\Vendor;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\CoaService;
use App\Services\InvitationService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class MasterDataController extends Controller
{
    public function accounts(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::CoaView), 403);

        return Inertia::render('accounts/Index', [
            'accounts' => $workspace->accounts()->with('parent')->orderBy('code')->get(),
            'types' => collect(AccountType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'canManage' => $request->user()->canIn($workspace, Permission::CoaManage),
        ]);
    }

    public function storeAccount(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::CoaManage), 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('accounts', 'code')->where('workspace_id', $workspace->id)],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'parent_id' => ['nullable', 'uuid'],
            'description' => ['nullable', 'string'],
        ]);

        $type = AccountType::from($data['type']);
        Account::query()->create([
            ...$data,
            'workspace_id' => $workspace->id,
            'normal_balance' => $type->defaultNormalBalance(),
            'is_active' => true,
        ]);

        return back()->with('success', 'Akun ditambahkan.');
    }

    public function updateAccount(Request $request, Workspace $workspace, Account $account): RedirectResponse
    {
        abort_unless($account->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::CoaManage), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $account->update($data);

        return back()->with('success', 'Akun diperbarui.');
    }

    public function importAccounts(Request $request, Workspace $workspace, CoaService $coa): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::CoaManage), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,xls']]);
        $rows = $coa->parseSpreadsheet($request->file('file'));
        $count = $coa->importRows($workspace, $rows);

        return back()->with('success', "{$count} akun diimpor.");
    }

    public function vendors(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::VendorManage) || $request->user()->canIn($workspace, Permission::CoaView), 403);

        return Inertia::render('vendors/Index', [
            'vendors' => $workspace->vendors()->with(['defaultExpenseAccount', 'accountMapping'])->orderBy('name')->paginate(30),
            'accounts' => $workspace->accounts()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'canManage' => $request->user()->canIn($workspace, Permission::VendorManage),
        ]);
    }

    public function storeVendor(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::VendorManage), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'default_expense_account_id' => ['nullable', 'uuid'],
        ]);
        Vendor::query()->create([
            ...$data,
            'workspace_id' => $workspace->id,
            'normalized_name' => Money::normalizeName($data['name']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Vendor ditambahkan.');
    }

    public function members(Request $request, Workspace $workspace): Response
    {
        abort_unless($request->user()->canIn($workspace, Permission::MemberManage) || $workspace->memberFor($request->user()), 403);

        return Inertia::render('members/Index', [
            'members' => $workspace->members()->with('user')->get(),
            'invitations' => $workspace->invitations()->latest()->limit(20)->get(),
            'roles' => collect(WorkspaceRole::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
            'canManage' => $request->user()->canIn($workspace, Permission::MemberManage),
        ]);
    }

    public function invite(Request $request, Workspace $workspace, InvitationService $invitations): RedirectResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::MemberManage), 403);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
        ]);
        try {
            $invitations->invite($workspace, $request->user(), $data['email'], WorkspaceRole::from($data['role']));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Undangan dikirim.');
    }

    public function updateMember(Request $request, Workspace $workspace, WorkspaceMember $member): RedirectResponse
    {
        abort_unless($member->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::MemberManage), 403);
        abort_if($member->role === WorkspaceRole::Owner, 403);

        $data = $request->validate(['role' => ['required', Rule::enum(WorkspaceRole::class)]]);
        $member->update(['role' => WorkspaceRole::from($data['role'])]);

        return back()->with('success', 'Peran anggota diperbarui.');
    }

    public function removeMember(Request $request, Workspace $workspace, WorkspaceMember $member): RedirectResponse
    {
        abort_unless($member->workspace_id === $workspace->id, 404);
        abort_unless($request->user()->canIn($workspace, Permission::MemberManage), 403);
        abort_if($member->role === WorkspaceRole::Owner, 403);
        $member->delete();

        return back()->with('success', 'Anggota dihapus.');
    }
}
