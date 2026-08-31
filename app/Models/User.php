<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
            'is_read_only' => 'boolean',
            'responsibilities' => 'array',
            'restrictions' => 'array',
            'allowed_modules' => 'array',
        ];
    }
}
