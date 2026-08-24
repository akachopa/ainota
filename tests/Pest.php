<?php

use App\Enums\MemberStatus;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceService;
use Database\Seeders\AccountTemplateSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function createWorkspaceFor(User $user, WorkspaceRole $role = WorkspaceRole::Owner): Workspace
{
    test()->seed(PlanSeeder::class);
    test()->seed(AccountTemplateSeeder::class);

    if ($role === WorkspaceRole::Owner) {
        return app(WorkspaceService::class)->create($user, [
            'name' => 'PT Uji',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'template' => 'jasa',
        ]);
    }

    $owner = User::factory()->create();
    $workspace = app(WorkspaceService::class)->create($owner, [
        'name' => 'PT Uji',
        'currency' => 'IDR',
        'timezone' => 'Asia/Jakarta',
        'locale' => 'id',
        'template' => 'jasa',
    ]);

    WorkspaceMember::query()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MemberStatus::Active,
        'joined_at' => now(),
    ]);

    return $workspace->fresh();
}
