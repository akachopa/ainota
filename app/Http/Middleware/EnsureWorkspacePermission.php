<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspacePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $workspace = CurrentWorkspace::get() ?? $request->route('workspace');
        $perm = Permission::from($permission);

        if (! $user || ! $workspace || ! $user->canIn($workspace, $perm)) {
            abort(403);
        }

        return $next($request);
    }
}
