<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $current = CurrentWorkspace::get() ?? $request->route('workspace');
        $workspaces = [];
        $permissions = [];

        if ($user) {
            $workspaces = $user->memberships()
                ->with('workspace')
                ->get()
                ->map(fn ($member) => [
                    'id' => $member->workspace->id,
                    'name' => $member->workspace->name,
                    'slug' => $member->workspace->slug,
                    'role' => $member->role->value,
                ])
                ->values();

            if ($current instanceof Workspace) {
                $permissions = collect(Permission::cases())
                    ->filter(fn (Permission $permission) => $user->canIn($current, $permission))
                    ->map(fn (Permission $permission) => $permission->value)
                    ->values();
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'workspaces' => $workspaces,
            'currentWorkspace' => $current instanceof Workspace ? [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ] : null,
            'permissions' => $permissions,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
