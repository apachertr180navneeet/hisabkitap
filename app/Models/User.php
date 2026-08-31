<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'email',
        'password',
        'role_name',
        'role_code',
        'badge_color',
        'badge_class',
        'avatar',
        'icon',
        'title',
        'tagline',
        'can_edit_bills',
        'can_import_excel',
        'can_record_corrections',
        'can_record_credit',
        'can_approve_sealing',
        'can_configure_pso',
        'can_edit_cutoff',
        'can_manage_users',
        'is_active',
        'is_read_only',
        'responsibilities',
        'restrictions',
        'allowed_modules',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_edit_bills' => 'boolean',
            'can_import_excel' => 'boolean',
            'can_record_corrections' => 'boolean',
            'can_record_credit' => 'boolean',
            'can_approve_sealing' => 'boolean',
            'can_configure_pso' => 'boolean',
            'can_edit_cutoff' => 'boolean',
            'can_manage_users' => 'boolean',
            'is_active' => 'boolean',
            'is_read_only' => 'boolean',
            'responsibilities' => 'array',
            'restrictions' => 'array',
            'allowed_modules' => 'array',
        ];
    }

    /**
     * Role checking helpers
     */
    public function isSuperAdmin(): bool
    {
        return in_array($this->role_code, ['SUPER_ADMIN', 'ADMIN']);
    }

    public function isOperator(): bool
    {
        return $this->role_code === 'OPERATOR';
    }

    public function isApprover(): bool
    {
        return in_array($this->role_code, ['APPROVER', 'AUDITOR']);
    }

    public function isReadOnly(): bool
    {
        return (bool) ($this->is_read_only ?? false);
    }

    /**
     * Permission check (Super admin has all permissions)
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return (bool) ($this->{$permission} ?? false);
    }

    /**
     * 3 Standard Predefined Roles
     */
    public static function getPredefinedRoles(): array
    {
        return [
            'SUPER_ADMIN' => [
                'role_code' => 'SUPER_ADMIN',
                'role_name' => 'Super Administrator',
                'badge_color' => 'danger',
                'badge_class' => 'bg-danger',
                'icon' => 'bi-shield-shaded',
                'title' => 'Master Super Administrator',
                'tagline' => 'Full master system control, user & role management, cutoff policy, and compliance enforcement.',
                'description' => 'Unrestricted access to all system modules, configurations, and administrative overrides.',
                'default_permissions' => [
                    'can_edit_bills' => true,
                    'can_import_excel' => true,
                    'can_record_corrections' => true,
                    'can_record_credit' => true,
                    'can_approve_sealing' => true,
                    'can_configure_pso' => true,
                    'can_edit_cutoff' => true,
                    'can_manage_users' => true,
                    'is_read_only' => false,
                ],
                'allowed_modules' => ['All Modules'],
            ],
            'OPERATOR' => [
                'role_code' => 'OPERATOR',
                'role_name' => 'PSO Operator',
                'badge_color' => 'primary',
                'badge_class' => 'bg-primary',
                'icon' => 'bi-person-badge-fill',
                'title' => 'Counter Accountant & PSO Operator',
                'tagline' => 'PSO series setup, Tally DayBook import, physical sequence check, and credit collection.',
                'description' => 'Handles day-to-day transaction entry, bill sequence verification, and payment classification.',
                'default_permissions' => [
                    'can_edit_bills' => true,
                    'can_import_excel' => true,
                    'can_record_corrections' => true,
                    'can_record_credit' => true,
                    'can_approve_sealing' => false,
                    'can_configure_pso' => true,
                    'can_edit_cutoff' => false,
                    'can_manage_users' => false,
                    'is_read_only' => false,
                ],
                'allowed_modules' => ['Dashboard', 'PSO Series', 'Tally Import', 'Bill Verification', 'Payment Classification', 'Corrections', 'Credit Collection', 'PSO Summary', 'Reports'],
            ],
            'APPROVER' => [
                'role_code' => 'APPROVER',
                'role_name' => 'Accounts Approver',
                'badge_color' => 'success',
                'badge_class' => 'bg-success',
                'icon' => 'bi-shield-check',
                'title' => 'Accounts Officer & Day Approver',
                'tagline' => 'Variance review, discrepancy resolution, final approval signoff, and digital SHA-256 seal lock.',
                'description' => 'Performs master reconciliation signoffs and executes cryptographic day seals.',
                'default_permissions' => [
                    'can_edit_bills' => false,
                    'can_import_excel' => false,
                    'can_record_corrections' => true,
                    'can_record_credit' => false,
                    'can_approve_sealing' => true,
                    'can_configure_pso' => false,
                    'can_edit_cutoff' => false,
                    'can_manage_users' => false,
                    'is_read_only' => false,
                ],
                'allowed_modules' => ['Dashboard', 'Bill Verification', 'Master Reconciliation', 'Approval & Sealing', '7-Day Retention', 'Reports'],
            ],
        ];
    }
}
