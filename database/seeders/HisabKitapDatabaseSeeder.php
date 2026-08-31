<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SystemSetting;

class HisabKitapDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin User ONLY
        User::create([
            'code' => 'usr_admin',
            'name' => 'Suresh Gupta',
            'email' => 'admin@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'Super Administrator',
            'role_code' => 'ADMIN',
            'badge_color' => 'danger',
            'badge_class' => 'bg-danger',
            'avatar' => 'SG',
            'icon' => 'bi-shield-shaded',
            'title' => 'Super Administrator & Compliance Officer',
            'tagline' => 'Full master system configuration, PSO counter series setup, cutoff policy, user access & system security.',
            'can_edit_bills' => true,
            'can_import_excel' => true,
            'can_record_corrections' => true,
            'can_record_credit' => true,
            'can_approve_sealing' => true,
            'can_configure_pso' => true,
            'can_edit_cutoff' => true,
            'can_manage_users' => true,
            'is_active' => true,
            'is_read_only' => false,
            'responsibilities' => [
                'Full Master Administrative Control',
                'PSO Series Setup & Counter Numbering Policy',
                'Daily PSO Cutoff Time (19:00 IST) & Rollover Rule Enforcement',
                'System User Security & Profile Management',
                'Master Compliance & Audit Trail Review'
            ],
            'restrictions' => [
                'Subject to statutory audit trails'
            ],
            'allowed_modules' => [
                'Dashboard',
                'PSO Series Management',
                'Tally Excel Import',
                'Bill Verification',
                'Payment Classification',
                'Corrections / Returns',
                'Credit Collection',
                'PSO Summary',
                'Master Reconciliation',
                'Approval & Sealing',
                '7-Day Retention Radar',
                'Reports & Exports',
                'Daily Cutoff & Settings',
                'User Profile & Security'
            ]
        ]);

        // 2. Default System Settings
        SystemSetting::setVal('business_date', date('Y-m-d'));
        SystemSetting::setVal('cutoff_time', '19:00');
        SystemSetting::setVal('cutoff_rollover_active', '1');

        // All other tables (bills, corrections, credit_collections, pso_configs, pso_daily_seals, pso_retentions, tally_imports, audit_logs) are left completely blank/empty for production use.
    }
}
