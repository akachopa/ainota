<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@example.test'],
            ['name' => 'Owner Demo', 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_platform_admin' => true],
        );
        $uploader = User::query()->updateOrCreate(
            ['email' => 'uploader@example.test'],
            ['name' => 'Uploader Demo', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $reviewer = User::query()->updateOrCreate(
            ['email' => 'reviewer@example.test'],
            ['name' => 'Reviewer Demo', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $approver = User::query()->updateOrCreate(
            ['email' => 'approver@example.test'],
            ['name' => 'Approver Demo', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );

        $workspace = Workspace::query()->where('slug', 'pt-maju-jaya')->first();
        if (! $workspace) {
            $workspace = app(WorkspaceService::class)->create($owner, [
                'name' => 'PT Maju Jaya',
                'legal_name' => 'PT Maju Jaya',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'locale' => 'id',
                'template' => 'jasa',
                'require_separate_approver' => true,
            ]);
        }

        foreach ([
            [$uploader, WorkspaceRole::Uploader],
            [$reviewer, WorkspaceRole::Reviewer],
            [$approver, WorkspaceRole::Approver],
        ] as [$user, $role]) {
            WorkspaceMember::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'user_id' => $user->id],
                ['role' => $role, 'status' => MemberStatus::Active, 'joined_at' => now()],
            );
        }

        $expense = fn (string $code) => $workspace->accounts()->where('code', $code)->value('id');

        foreach ([
            ['Pertamina', '610104'],
            ['PLN', '610102'],
            ['Telkom', '610103'],
        ] as [$name, $code]) {
            Vendor::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'normalized_name' => Money::normalizeName($name)],
                [
                    'name' => $name,
                    'default_expense_account_id' => $expense($code),
                    'is_active' => true,
                ],
            );
        }
    }
}
