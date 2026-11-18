<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * MODEL layer: a BrandBondhu account, its role and its permissions.
 */
#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token', 'otp_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SELLER = 'seller';
    public const ROLE_COLLABORATOR = 'collaborator';
    public const ROLE_DESIGNER = 'designer';
    public const ROLE_ADMIN = 'admin';

    public const ROLES = [self::ROLE_SELLER, self::ROLE_COLLABORATOR, self::ROLE_DESIGNER, self::ROLE_ADMIN];

    /** Same defaults as the database columns, so a new model is correct before it is reloaded. */
    protected $attributes = [
        'role' => self::ROLE_SELLER,
        'is_active' => true,
        'ai_credits' => 0,
        'otp_attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'otp_expires_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'ai_credits' => 'integer',
            'otp_attempts' => 'integer',
        ];
    }

    // ----- Roles and permissions (Feature 2) -----

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * The seller account whose workspace this user works in.
     * Sellers own their workspace; collaborators work in their seller's workspace.
     */
    public function workspaceOwner(): ?User
    {
        if ($this->role === self::ROLE_SELLER) {
            return $this;
        }

        if ($this->role === self::ROLE_COLLABORATOR) {
            $membership = $this->membership;

            return $membership && $membership->status === TeamMember::STATUS_ACTIVE ? $membership->seller : null;
        }

        return null;
    }

    /** @return list<string> */
    public function permissionList(): array
    {
        if ($this->role === self::ROLE_COLLABORATOR) {
            return $this->workspaceOwner() ? array_values($this->membership->permissions ?? []) : [];
        }

        return config("brandbondhu.role_permissions.{$this->role}", []);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionList(), true);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->name)) ?: [];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

        return implode('', $letters) ?: 'U';
    }

    // ----- Relationships -----

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    public function designerProfile(): HasOne
    {
        return $this->hasOne(DesignerProfile::class);
    }

    /** Team membership of a collaborator. */
    public function membership(): HasOne
    {
        return $this->hasOne(TeamMember::class, 'user_id');
    }

    /** Collaborators invited by a seller. */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'seller_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function brandKits(): HasMany
    {
        return $this->hasMany(BrandKit::class, 'seller_id');
    }

    public function styleGuides(): HasMany
    {
        return $this->hasMany(StyleGuide::class, 'seller_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'seller_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'seller_id');
    }
}
