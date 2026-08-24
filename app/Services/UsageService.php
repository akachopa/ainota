<?php

namespace App\Services;

use App\Enums\UsageType;
use App\Models\Plan;
use App\Models\UsageLedger;
use App\Models\Workspace;
use App\Models\WorkspaceUsage;
use Illuminate\Support\Carbon;
use RuntimeException;

class UsageService
{
    public function currentPlan(Workspace $workspace): Plan
    {
        $subscription = $workspace->subscription()->with('plan')->first();

        if ($subscription?->plan) {
            return $subscription->plan;
        }

        return Plan::query()->where('slug', 'free')->firstOrFail();
    }

    public function pagesUsedThisMonth(Workspace $workspace): int
    {
        $period = now()->format('Y-m');
        $usage = WorkspaceUsage::query()->forWorkspace($workspace)->where('period', $period)->first();

        return (int) ($usage?->ai_pages ?? 0);
    }

    public function assertCanProcessPages(Workspace $workspace, int $pages): void
    {
        $plan = $this->currentPlan($workspace);
        $used = $this->pagesUsedThisMonth($workspace);

        if ($used + $pages > $plan->pages_per_month) {
            throw new RuntimeException('Kuota halaman AI bulan ini sudah habis.');
        }
    }

    public function recordAiPages(
        Workspace $workspace,
        int $pages,
        string $referenceType,
        string $referenceId,
        ?float $providerCost = null,
        bool $charge = true,
    ): void {
        if (! $charge || $pages < 1) {
            return;
        }

        $subscription = $workspace->subscription()->first();

        UsageLedger::query()->create([
            'workspace_id' => $workspace->id,
            'subscription_id' => $subscription?->id,
            'usage_type' => UsageType::AiPage,
            'quantity' => $pages,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'provider_cost' => $providerCost,
            'metadata' => ['charged' => true],
        ]);

        $period = Carbon::now()->format('Y-m');
        $usage = WorkspaceUsage::query()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'period' => $period],
            ['ai_pages' => 0, 'provider_cost' => 0],
        );
        $usage->increment('ai_pages', $pages);
        if ($providerCost) {
            $usage->increment('provider_cost', $providerCost);
        }
    }

    public function remainingPages(Workspace $workspace): int
    {
        return max(0, $this->currentPlan($workspace)->pages_per_month - $this->pagesUsedThisMonth($workspace));
    }
}
