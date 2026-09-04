<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Merchants\MerchantType;
use App\Enums\RoleType;
use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantUser;
use App\Traits\ActivityLogs;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $avatar_path
 * @property RoleType $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read bool|null $activities_as_subject_exists
 * @property-read MerchantUser|null $pivot
 * @property-read Collection<int, Merchant> $merchants
 * @property-read int|null $merchants_count
 * @property-read bool|null $merchants_exists
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read bool|null $notifications_exists
 * @property-read Collection<int, Session> $sessions
 * @property-read int|null $sessions_count
 * @property-read bool|null $sessions_exists
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasName, HasTenants
{
    use ActivityLogs;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'avatar_path',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => RoleType::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_user', 'user_id', 'merchant_id')
            ->using(MerchantUser::class)
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id', 'id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();
        $role = $this->role;

        return match ($role) {
            RoleType::SuperAdmin => true,
            default => $panelId === 'merchant',
        };
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->role === RoleType::SuperAdmin) {
            return true;
        }

        $key = "user:{$this->id}:can_access_tenant:{$tenant->getTable()}:{$tenant->getKey()}";

        return Cache::flexible($key, [120, 140], fn () => $this->merchants()->whereKey($tenant)->exists());
    }

    /**
     * @return array<Model> | Collection
     */
    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->role === RoleType::SuperAdmin) {
            return Merchant::query()
                ->where('type', MerchantType::Merchant)
                ->get();
        }

        return $this->merchants;
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        if ($this->role === RoleType::SuperAdmin) {
            return Merchant::query()
                ->where('type', MerchantType::Merchant)
                ->first();
        }

        return $this->merchants()->first();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (filled($this->avatar_path)) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return null;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
