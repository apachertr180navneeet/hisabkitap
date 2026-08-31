<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'action',
        'details',
        'ip_address',
    ];

    public static function log($action, $details, $userName = null)
    {
        if (!$userName) {
            $user = session('active_user');
            $userName = $user ? $user['name'] : (auth()->check() ? auth()->user()->name : 'System');
        }

        return static::create([
            'user_name' => $userName,
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
