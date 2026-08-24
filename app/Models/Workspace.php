<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\WorkspaceStatus;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property string $slug
 * @property string $currency
 * @property string $timezone
 * @property string $locale
 * @property WorkspaceStatus $status
 * @property-read WorkspaceSetting|null $settings
 * @property-read Subscription|null $subscription
 * @property-read User|null $owner
 * @property-read Plan|null $plan
 */
#[Fillable([
    'owner_user_id',
    'name',
    'slug',
    'legal_name',
    'currency',
    'timezone',
    'locale',
    'status',
    'plan_id',
])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => WorkspaceStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace): void {
            if (blank($workspace->slug)) {
                $workspace->slug = static::uniqueSlug($workspace->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /**
     * @return HasOne<WorkspaceSetting, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(WorkspaceSetting::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latest('created_at');
    }

    public function memberFor(User $user): ?WorkspaceMember
    {
        return $this->members()->where('user_id', $user->id)->first();
    }

    public function userCan(User $user, Permission $permission): bool
    {
        if ($user->is_platform_admin) {
            return false;
        }

        $member = $this->memberFor($user);

        return $member?->hasPermission($permission) ?? false;
    }
}
