<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceStatus;
use App\Models\Account;
use App\Models\AccountTemplate;
use App\Models\PaymentAccountMapping;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceSetting;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkspaceService
{
    public function __construct(
        private CoaService $coa,
        private ActivityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Workspace
    {
        $owned = Workspace::query()->where('owner_user_id', $user->id)->count();
        $free = Plan::query()->where('slug', 'free')->firstOrFail();

        if ($owned >= $free->max_workspaces && $owned > 0) {
            $starter = Plan::query()->where('slug', 'starter')->first();
            if ($owned >= ($starter?->max_workspaces ?? 5)) {
                throw new RuntimeException('Batas workspace untuk paket Anda sudah tercapai.');
            }
        }

        return DB::transaction(function () use ($user, $data, $free) {
            $workspace = Workspace::query()->create([
                'owner_user_id' => $user->id,
                'name' => $data['name'],
                'slug' => Workspace::uniqueSlug($data['name']),
                'legal_name' => $data['legal_name'] ?? null,
                'currency' => $data['currency'] ?? 'IDR',
                'timezone' => $data['timezone'] ?? 'Asia/Jakarta',
                'locale' => $data['locale'] ?? 'id',
                'status' => WorkspaceStatus::Active,
                'plan_id' => $free->id,
            ]);

            WorkspaceMember::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceRole::Owner,
                'status' => MemberStatus::Active,
                'joined_at' => now(),
            ]);

            $settings = WorkspaceSetting::query()->create([
                'workspace_id' => $workspace->id,
                'require_approval' => true,
                'require_separate_approver' => (bool) ($data['require_separate_approver'] ?? false),
                'coa_template_slug' => $data['template'] ?? null,
            ]);

            Subscription::query()->create([
                'workspace_id' => $workspace->id,
                'plan_id' => $free->id,
                'status' => 'active',
                'starts_at' => now(),
                'renews_at' => now()->addMonth(),
            ]);

            if (! empty($data['template']) && $data['template'] !== 'empty') {
                $template = AccountTemplate::query()->where('slug', $data['template'])->first();
                if ($template) {
                    $this->coa->copyTemplate($workspace, $template);
                    $this->seedDefaultMappings($workspace);
                    $cash = Account::query()->forWorkspace($workspace)->where('code', '110101')->first();
                    $settings->update(['default_cash_account_id' => $cash?->id]);
                }
            }

            $this->logger->log('workspace.created', $workspace, $user, $workspace, after: $workspace->toArray());

            return $workspace;
        });
    }

    public function seedDefaultMappings(Workspace $workspace): void
    {
        $pairs = [
            ['cash', 'Kas', '110101'],
            ['bca', 'BCA', '110201'],
            ['mandiri', 'Mandiri', '110202'],
            ['credit_card', 'Kartu Kredit', '210301'],
        ];

        foreach ($pairs as [$keyword, $label, $code]) {
            $account = Account::query()->forWorkspace($workspace)->where('code', $code)->first();
            if (! $account) {
                continue;
            }

            PaymentAccountMapping::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'keyword' => $keyword],
                ['label' => $label, 'account_id' => $account->id, 'is_default' => $keyword === 'cash'],
            );
        }
    }
}
