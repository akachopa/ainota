<?php

namespace App\Support;

use App\Models\Workspace;
use App\Models\WorkspaceMember;

class CurrentWorkspace
{
    private static ?Workspace $workspace = null;

    private static ?WorkspaceMember $member = null;

    public static function set(?Workspace $workspace, ?WorkspaceMember $member = null): void
    {
        self::$workspace = $workspace;
        self::$member = $member;
    }

    public static function get(): ?Workspace
    {
        return self::$workspace;
    }

    public static function member(): ?WorkspaceMember
    {
        return self::$member;
    }

    public static function id(): ?string
    {
        return self::$workspace?->id;
    }

    public static function forget(): void
    {
        self::$workspace = null;
        self::$member = null;
    }
}
