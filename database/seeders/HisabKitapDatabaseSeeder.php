<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\PsoConfig;
use App\Models\TallyImport;
use App\Models\Bill;
use App\Models\Correction;
use App\Models\CreditCollection;
use App\Models\PsoDailySeal;
use App\Models\PsoRetention;
use App\Models\SystemSetting;
use App\Models\AuditLog;

class HisabKitapDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users & Personas
        $users = [
            [
                'code' => 'usr_01',
                'name' => 'Ramesh Sharma',
                'email' => 'ramesh.sharma@hisabkitap.in',
                'password' => Hash::make('password'),
                'role_name' => 'Accountant (PSO Operator)',
                'role_code' => 'OPERATOR',
                'badge_color' => 'primary',
                'badge_class' => 'bg-primary',
                'avatar' => 'RS',
                'icon' => 'bi-person-badge-fill',
                'title' => 'Counter Accountant & PSO Data Operator',
                'tagline' => 'PSO series creation, daily data entry, Tally Excel import, bill checking, and credit collection tracking.',
                'can_edit_bills' => true,
                'can_import_excel' => true,
                'can_record_corrections' => true,
                'can_record_credit' => true,
                'can_approve_sealing' => false,
                'can_configure_pso' => true,
                'can_edit_cutoff' => false,
                'is_read_only' => false,
                'responsibilities' => [
                    'Configure and create daily PSO Counter Series (Prefixes, Numbering, Specials)',
                    'Import Tally Sales Register (Excel / CSV)',
                    'Verify physical counter bill bundles (CB 01-10, RB 01-10, Specials)',
                    'Record Cash Discount waivers and Goods Returns adjustments',
                    'Log salesman / cashier credit recoveries',
                    'Generate draft PSO summaries and request reconciliation signoff'
                ],
                'restrictions' => [
                    'Cannot finalize approval or digitally seal daily records (Requires Accounts Officer: Pooja Verma)',
                    'Cannot modify System-wide Cutoff Time policy (Requires Super Admin: Suresh Gupta)',
                    'Cannot unseal or unlock archived business dates'
                ],
                'allowed_modules' => ['Dashboard', 'PSO Series Management', 'Tally Excel Import', 'Bill Verification', 'Payment Classification', 'Corrections / Returns', 'Credit Collection', 'PSO Summary', 'Master Reconciliation (Draft View)', 'Reports & Exports']
            ],
            [
                'code' => 'usr_02',
                'name' => 'Pooja Verma',
                'email' => 'pooja.verma@hisabkitap.in',
                'password' => Hash::make('password'),
                'role_name' => 'Accounts Officer (Approver)',
                'role_code' => 'APPROVER',
                'badge_color' => 'success',
                'badge_class' => 'bg-success',
                'avatar' => 'PV',
                'icon' => 'bi-check-circle-fill',
                'title' => 'Senior Accounts Officer & Authorization Signatory',
                'tagline' => 'Final review of reconciliation variance, discount approvals, and Digital Cryptographic Sealing.',
                'can_edit_bills' => true,
                'can_import_excel' => true,
                'can_record_corrections' => true,
                'can_record_credit' => true,
                'can_approve_sealing' => true,
                'can_configure_pso' => false,
                'can_edit_cutoff' => false,
                'is_read_only' => false,
                'responsibilities' => [
                    'Review Master Reconciliation variance and gate checks',
                    'Authorize high-value corrections, returns & missing bill resolutions',
                    'Execute official PSO Approval & Digital Sealing with Hash Lock',
                    'Authorize emergency unsealing with mandatory audit remark',
                    'Sign off on formal daily financial reconciliation compliance'
                ],
                'restrictions' => [
                    'Cannot alter Master PSO Counter numbering rules or system architecture'
                ],
                'allowed_modules' => ['Dashboard', 'Tally Excel Import', 'Bill Verification', 'Payment Classification', 'Corrections / Returns', 'Credit Collection', 'PSO Summary', 'Master Reconciliation', 'Approval & Sealing (Authorized)', '7-Day Retention', 'Reports & Exports']
            ],
            [
                'code' => 'usr_03',
                'name' => 'Suresh Gupta',
                'email' => 'admin@hisabkitap.in',
                'password' => Hash::make('password'),
                'role_name' => 'System Administrator',
                'role_code' => 'ADMIN',
                'badge_color' => 'danger',
                'badge_class' => 'bg-danger',
                'avatar' => 'SG',
                'icon' => 'bi-shield-lock-fill',
                'title' => 'ERP & Master Configuration Administrator',
                'tagline' => 'Full master control. PSO counter series setup, cutoff time policy, user access & system locks.',
                'can_edit_bills' => true,
                'can_import_excel' => true,
                'can_record_corrections' => true,
                'can_record_credit' => true,
                'can_approve_sealing' => true,
                'can_configure_pso' => true,
                'can_edit_cutoff' => true,
                'is_read_only' => false,
                'responsibilities' => [
                    'Configure Master PSO counters, series start/end numbers, prefixes & specials',
                    'Configure Day Cutoff Time (e.g. 7:00 PM) and Rollover rules',
                    'Manage User Access, Roles, Security and Permissions',
                    'Emergency lock/unseal operations and data rollbacks',
                    'Full unconstrained access to all ERP subsystems'
                ],
                'restrictions' => [
                    'Must follow audit protocol when modifying active PSO master numbering'
                ],
                'allowed_modules' => ['All System Modules (Full Administrative Master Control)']
            ],
            [
                'code' => 'usr_04',
                'name' => 'Vikram Mehta',
                'email' => 'auditor@hisabkitap.in',
                'password' => Hash::make('password'),
                'role_name' => 'Internal Auditor',
                'role_code' => 'AUDITOR',
                'badge_color' => 'warning',
                'badge_class' => 'bg-warning text-dark',
                'avatar' => 'VM',
                'icon' => 'bi-eye-fill',
                'title' => 'Internal Audit & Statutory Compliance Officer',
                'tagline' => 'Independent oversight. 7-day retention inspection, discrepancy logs, and audit trail compliance.',
                'can_edit_bills' => false,
                'can_import_excel' => false,
                'can_record_corrections' => false,
                'can_record_credit' => false,
                'can_approve_sealing' => false,
                'can_configure_pso' => false,
                'can_edit_cutoff' => false,
                'is_read_only' => true,
                'responsibilities' => [
                    'Conduct independent spot-checks on physical bill verification',
                    'Audit 7-day retention log and daily digital seal hashes',
                    'Track discrepancy histories, post-cutoff rollover bills and corrections',
                    'Export certified reconciliation audit logs to PDF/Excel'
                ],
                'restrictions' => [
                    'STRICTLY READ-ONLY: Cannot modify bill amounts, payment types, or statuses',
                    'Cannot import Excel or overwrite financial data',
                    'Cannot seal, unseal, or alter PSO configurations'
                ],
                'allowed_modules' => ['All Modules (Read-Only Inspection Mode)', 'Audit Trail', '7-Day Retention', 'Certified Audit Reports']
            ]
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }

        // 2. PSO Configurations
        $pso1 = PsoConfig::updateOrCreate(['code' => 'PSO-1'], [
            'name' => 'PSO 1 - Main Wholesale Counter',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'specials' => [],
            'operator_name' => 'Ramesh Sharma',
            'is_active' => true,
            'description' => 'Counter Bills CB 01 to CB 10'
        ]);

        $pso2 = PsoConfig::updateOrCreate(['code' => 'PSO-2'], [
            'name' => 'PSO 2 - Key Accounts & Special ITC',
            'prefix' => 'CB',
            'start_no' => 11,
            'end_no' => 20,
            'specials' => ['ITC 01', 'ITC 03'],
            'operator_name' => 'Rajesh Kumar',
            'is_active' => true,
            'description' => 'Counter Bills CB 11 to CB 20 + ITC 01, ITC 03'
        ]);

        $pso3 = PsoConfig::updateOrCreate(['code' => 'PSO-3'], [
            'name' => 'PSO 3 - Retail Counter & Instant Delivery',
            'prefix' => 'RB',
            'start_no' => 1,
            'end_no' => 10,
            'specials' => [],
            'operator_name' => 'Amit Saxena',
            'is_active' => true,
            'description' => 'Retail Bills RB 01 to RB 10'
        ]);

        // 3. Tally Imports
        $import1 = TallyImport::create([
            'filename' => 'Tally_DayBook_14Aug2026.xlsx',
            'business_date' => '2026-08-14',
            'total_records' => 32,
            'total_amount' => 700000,
            'status' => 'Imported & Scanned',
            'operator_name' => 'Ramesh Sharma'
        ]);

        TallyImport::create([
            'filename' => 'Tally_DayBook_13Aug2026.xlsx',
            'business_date' => '2026-08-13',
            'total_records' => 28,
            'total_amount' => 645000,
            'status' => 'Sealed & Reconciled',
            'operator_name' => 'Ramesh Sharma'
        ]);

        TallyImport::create([
            'filename' => 'Tally_DayBook_12Aug2026.xlsx',
            'business_date' => '2026-08-12',
            'total_records' => 30,
            'total_amount' => 692000,
            'status' => 'Sealed & Reconciled',
            'operator_name' => 'Ramesh Sharma'
        ]);

        // 4. Bills for 2026-08-14
        $billsData = [
            // PSO 1: CB 01 to CB 10
            ['bill_no' => 'CB 01', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '11:15', 'customer' => 'Gupta Traders', 'amount' => 35000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 35000, 'status' => 'Matched', 'remark' => 'Verified in physical bundle'],
            ['bill_no' => 'CB 02', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '12:00', 'customer' => 'Kailash Supermarket', 'amount' => 17500, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 17500, 'status' => 'Missing', 'remark' => 'Pending physical slip from counter 1'],
            ['bill_no' => 'CB 03', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '13:20', 'customer' => 'Modern Departmental', 'amount' => 22500, 'payment' => 'Paytm', 'cd' => 500, 'refund' => 0, 'net' => 22000, 'status' => 'Matched', 'remark' => 'UPI Ref: 42198730918'],
            ['bill_no' => 'CB 04', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '14:10', 'customer' => 'Shri Ram Provision', 'amount' => 18000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 18000, 'status' => 'Matched', 'remark' => 'HDFC Chq #004521'],
            ['bill_no' => 'CB 05', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '14:45', 'customer' => 'Balaji Enterprises', 'amount' => 28000, 'payment' => 'Credit', 'cd' => 0, 'refund' => 0, 'net' => 28000, 'status' => 'Matched', 'remark' => 'Due date: 21-Aug-2026'],
            ['bill_no' => 'CB 06', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '15:30', 'customer' => 'Vikas General Store', 'amount' => 15000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 15000, 'status' => 'Matched', 'remark' => 'Cashier verified'],
            ['bill_no' => 'CB 07', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '16:00', 'customer' => 'Krishna Agencies', 'amount' => 32000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 32000, 'status' => 'Matched', 'remark' => 'Paytm QR code slip attached'],
            ['bill_no' => 'CB 08', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '16:40', 'customer' => 'Mehta & Sons', 'amount' => 24000, 'payment' => 'Credit', 'cd' => 1000, 'refund' => 0, 'net' => 23000, 'status' => 'Matched', 'remark' => 'Salesman: Rajesh Kumar'],
            ['bill_no' => 'CB 09', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '17:15', 'customer' => 'Ambika Mart', 'amount' => 16000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 16000, 'status' => 'Matched', 'remark' => 'SBI Chq #883921'],
            ['bill_no' => 'CB 10', 'pso_code' => 'PSO-1', 'pso_config_id' => $pso1->id, 'expected' => true, 'tally_found' => true, 'time' => '18:30', 'customer' => 'Sharma Kirana', 'amount' => 20000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 20000, 'status' => 'Matched', 'remark' => 'Closing counter bill'],

            // PSO 2: CB 11 to CB 20 + ITC 01, ITC 03
            ['bill_no' => 'CB 11', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '11:40', 'customer' => 'Ahuja Wholesalers', 'amount' => 30000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 30000, 'status' => 'Matched', 'remark' => 'Full cash received'],
            ['bill_no' => 'CB 12', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '12:15', 'customer' => 'Agarwal Sweets & Provisions', 'amount' => 25000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 25000, 'status' => 'Matched', 'remark' => 'Paytm Soundbox confirmed'],
            ['bill_no' => 'CB 13', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '13:00', 'customer' => 'New Delhi Mart', 'amount' => 45000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 45000, 'status' => 'Matched', 'remark' => 'ICICI Chq #112093'],
            ['bill_no' => 'CB 14', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '14:10', 'customer' => 'Om Sai Enterprises', 'amount' => 22000, 'payment' => 'Credit', 'cd' => 0, 'refund' => 0, 'net' => 22000, 'status' => 'Matched', 'remark' => 'Salesman: Amit Sharma'],
            ['bill_no' => 'CB 15', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '15:20', 'customer' => 'Garg Confectioners', 'amount' => 18000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 18000, 'status' => 'Matched', 'remark' => 'Counter 2 verify'],
            ['bill_no' => 'CB 16', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '16:05', 'customer' => 'Ratan Stores', 'amount' => 15000, 'payment' => 'Cancelled', 'cd' => 0, 'refund' => 0, 'net' => 0, 'status' => 'Cancelled', 'remark' => 'Customer cancelled before dispatch'],
            ['bill_no' => 'CB 17', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '16:50', 'customer' => 'City Cash & Carry', 'amount' => 35000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 35000, 'status' => 'Matched', 'remark' => 'Axis Bank Chq #40921'],
            ['bill_no' => 'CB 18', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '17:35', 'customer' => 'Jindal Retailers', 'amount' => 20000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 20000, 'status' => 'Matched', 'remark' => 'UPI verified'],
            ['bill_no' => 'CB 19', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '18:10', 'customer' => 'Universal Traders', 'amount' => 27000, 'payment' => 'Cash', 'cd' => 1000, 'refund' => 1000, 'net' => 25000, 'status' => 'Matched', 'remark' => 'Return 1 item damaged'],
            ['bill_no' => 'CB 20', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '18:45', 'customer' => 'Sunil Brother Co', 'amount' => 23000, 'payment' => 'Credit', 'cd' => 0, 'refund' => 0, 'net' => 23000, 'status' => 'Matched', 'remark' => 'Salesman: Rajesh Kumar'],
            ['bill_no' => 'ITC 01', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '15:00', 'customer' => 'ITC Direct Depo Wholesale', 'amount' => 25000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 25000, 'status' => 'Matched', 'remark' => 'Direct Company Stock Transfer Bill'],
            ['bill_no' => 'ITC 03', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => true, 'tally_found' => true, 'time' => '17:40', 'customer' => 'ITC Food Hub Distributor', 'amount' => 20000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 20000, 'status' => 'Matched', 'remark' => 'Special Corporate Promo Bill'],

            // PSO 3: RB 01 to RB 10
            ['bill_no' => 'RB 01', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '10:30', 'customer' => 'Direct Walk-in 101', 'amount' => 12500, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 12500, 'status' => 'Matched', 'remark' => 'Express POS slip'],
            ['bill_no' => 'RB 02', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '11:20', 'customer' => 'Direct Walk-in 102', 'amount' => 18000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 18000, 'status' => 'Matched', 'remark' => 'QR scan payment'],
            ['bill_no' => 'RB 03', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '12:40', 'customer' => 'Direct Walk-in 103', 'amount' => 14000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 14000, 'status' => 'Matched', 'remark' => 'Exact cash collected'],
            ['bill_no' => 'RB 04', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '13:50', 'customer' => 'Direct Walk-in 104', 'amount' => 22000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 22000, 'status' => 'Matched', 'remark' => 'Bank of Baroda Chq #9012'],
            ['bill_no' => 'RB 05', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '14:30', 'customer' => 'Direct Walk-in 105', 'amount' => 16000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 16000, 'status' => 'Matched', 'remark' => 'UPI transfer verified'],
            ['bill_no' => 'RB 06', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '15:45', 'customer' => 'Direct Walk-in 106 (Govt School)', 'amount' => 25000, 'payment' => 'Credit', 'cd' => 0, 'refund' => 0, 'net' => 25000, 'status' => 'Matched', 'remark' => 'Local institutional credit (Govt School)'],
            ['bill_no' => 'RB 07', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '16:20', 'customer' => 'Direct Walk-in 107', 'amount' => 11000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 11000, 'status' => 'Matched', 'remark' => 'Retail verified'],
            ['bill_no' => 'RB 08', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '17:10', 'customer' => 'Direct Walk-in 108', 'amount' => 19000, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 19000, 'status' => 'Matched', 'remark' => 'GooglePay confirmed'],
            ['bill_no' => 'RB 09', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '18:00', 'customer' => 'Direct Walk-in 109', 'amount' => 15000, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 15000, 'status' => 'Matched', 'remark' => 'Counter cash intact'],
            ['bill_no' => 'RB 10', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => true, 'tally_found' => true, 'time' => '18:50', 'customer' => 'Direct Walk-in 110', 'amount' => 25000, 'payment' => 'Check', 'cd' => 0, 'refund' => 0, 'net' => 25000, 'status' => 'Matched', 'remark' => 'PNB Chq #332014'],

            // Post-Cutoff Demo Bills (> 7:00 PM)
            ['bill_no' => 'CB 21', 'pso_code' => 'PSO-2', 'pso_config_id' => $pso2->id, 'expected' => false, 'tally_found' => true, 'time' => '19:25', 'customer' => 'Night Owl Grocers', 'amount' => 14500, 'payment' => 'Cash', 'cd' => 0, 'refund' => 0, 'net' => 14500, 'status' => 'Next Day PSO', 'remark' => 'Entered after 7:00 PM cutoff - Assigned to 15-Aug PSO', 'is_post_cutoff' => true],
            ['bill_no' => 'RB 11', 'pso_code' => 'PSO-3', 'pso_config_id' => $pso3->id, 'expected' => false, 'tally_found' => true, 'time' => '19:40', 'customer' => 'Late Walk-in 111', 'amount' => 8200, 'payment' => 'Paytm', 'cd' => 0, 'refund' => 0, 'net' => 8200, 'status' => 'Next Day PSO', 'remark' => 'Entered after 7:00 PM cutoff - Assigned to 15-Aug PSO', 'is_post_cutoff' => true],
        ];

        $createdBills = [];
        foreach ($billsData as $b) {
            $created = Bill::create([
                'bill_no' => $b['bill_no'],
                'pso_config_id' => $b['pso_config_id'],
                'pso_code' => $b['pso_code'],
                'tally_import_id' => $import1->id,
                'business_date' => '2026-08-14',
                'bill_time' => $b['time'],
                'customer_name' => $b['customer'],
                'amount' => $b['amount'],
                'payment_type' => $b['payment'],
                'cd_amount' => $b['cd'],
                'refund_amount' => $b['refund'],
                'net_amount' => $b['net'],
                'status' => $b['status'],
                'is_expected' => $b['expected'],
                'tally_found' => $b['tally_found'],
                'is_post_cutoff' => $b['is_post_cutoff'] ?? false,
                'remark' => $b['remark'],
                'verified_by' => ($b['status'] === 'Matched') ? 'Ramesh Sharma' : null,
                'verified_at' => ($b['status'] === 'Matched') ? '2026-08-14 18:45:00' : null,
            ]);
            $createdBills[$b['bill_no']] = $created;
        }

        // 5. Corrections / Returns
        Correction::create([
            'corr_code' => 'CORR-01',
            'bill_id' => $createdBills['CB 03']->id ?? null,
            'bill_no' => 'CB 03',
            'original_amount' => 22500,
            'correction_type' => 'Cash Discount',
            'cd_amount' => 500,
            'goods_return_amount' => 0,
            'refund_amount' => 0,
            'net_adjustment' => -500,
            'reason' => 'Volume rebate discount authorized on billing',
            'approved_by' => 'Pooja Verma',
            'created_at' => '2026-08-14 14:05:00'
        ]);

        Correction::create([
            'corr_code' => 'CORR-02',
            'bill_id' => $createdBills['CB 08']->id ?? null,
            'bill_no' => 'CB 08',
            'original_amount' => 24000,
            'correction_type' => 'Cash Discount',
            'cd_amount' => 1000,
            'goods_return_amount' => 0,
            'refund_amount' => 0,
            'net_adjustment' => -1000,
            'reason' => 'Early payment incentive 4% agreed',
            'approved_by' => 'Pooja Verma',
            'created_at' => '2026-08-14 17:00:00'
        ]);

        Correction::create([
            'corr_code' => 'CORR-03',
            'bill_id' => $createdBills['CB 19']->id ?? null,
            'bill_no' => 'CB 19',
            'original_amount' => 27000,
            'correction_type' => 'Goods Return',
            'cd_amount' => 1000,
            'goods_return_amount' => 1000,
            'refund_amount' => 1000,
            'net_adjustment' => -2000,
            'reason' => '1 carton crushed during transit + spot discount',
            'approved_by' => 'Suresh Gupta',
            'created_at' => '2026-08-14 18:30:00'
        ]);

        // 6. Credit Collections
        $creditList = [
            ['bill_no' => 'CB 05', 'customer' => 'Balaji Enterprises', 'salesman' => 'Rajesh Kumar', 'bill_amount' => 28000, 'paid' => 0, 'out' => 28000, 'status' => 'Pending', 'due' => '2026-08-21', 'remark' => 'Follow-up scheduled on Monday'],
            ['bill_no' => 'CB 08', 'customer' => 'Mehta & Sons', 'salesman' => 'Rajesh Kumar', 'bill_amount' => 23000, 'paid' => 10000, 'out' => 13000, 'status' => 'Partially Collected', 'due' => '2026-08-19', 'remark' => '₹10,000 received via GPay, balance next week'],
            ['bill_no' => 'CB 14', 'customer' => 'Om Sai Enterprises', 'salesman' => 'Amit Sharma', 'bill_amount' => 22000, 'paid' => 0, 'out' => 22000, 'status' => 'Pending', 'due' => '2026-08-28', 'remark' => '14 days credit cycle approved'],
            ['bill_no' => 'CB 20', 'customer' => 'Sunil Brother Co', 'salesman' => 'Rajesh Kumar', 'bill_amount' => 23000, 'paid' => 23000, 'out' => 0, 'status' => 'Collected', 'due' => '2026-08-16', 'remark' => 'Full payment received by Cheque #89112'],
            ['bill_no' => 'RB 06', 'customer' => 'Direct Walk-in 106 (Govt School)', 'salesman' => 'Amit Sharma', 'bill_amount' => 25000, 'paid' => 0, 'out' => 25000, 'status' => 'Pending', 'due' => '2026-08-30', 'remark' => 'Government treasury bill voucher submitted'],
        ];

        foreach ($creditList as $c) {
            CreditCollection::create([
                'bill_id' => $createdBills[$c['bill_no']]->id ?? null,
                'bill_no' => $c['bill_no'],
                'customer_name' => $c['customer'],
                'salesman_name' => $c['salesman'],
                'bill_date' => '2026-08-14',
                'due_date' => $c['due'],
                'bill_amount' => $c['bill_amount'],
                'paid_amount' => $c['paid'],
                'outstanding_amount' => $c['out'],
                'collection_status' => $c['status'],
                'remark' => $c['remark'],
            ]);
        }

        // 7. PSO Daily Seals
        PsoDailySeal::create([
            'business_date' => '2026-08-14',
            'tally_total' => 700000,
            'pso_total' => 682500,
            'difference' => 17500,
            'is_reconciled' => false,
            'is_sealed' => false,
            'status' => 'Draft',
            'remarks' => 'Discrepancy of ₹17,500 detected on Missing Bill CB 02'
        ]);

        // 8. 7-Day Retention Radar
        $retentions = [
            ['pso_code' => 'PSO 2', 'business_date' => '2026-08-14', 'created_date_formatted' => '14-Aug-2026', 'days_remaining' => 7, 'total_amount' => 280000, 'status' => 'Pending Approval', 'badge_class' => 'bg-warning text-dark'],
            ['pso_code' => 'PSO 1', 'business_date' => '2026-08-13', 'created_date_formatted' => '13-Aug-2026', 'days_remaining' => 6, 'total_amount' => 215000, 'status' => 'Sealed & Approved', 'badge_class' => 'bg-success'],
            ['pso_code' => 'PSO 3', 'business_date' => '2026-08-12', 'created_date_formatted' => '12-Aug-2026', 'days_remaining' => 5, 'total_amount' => 168000, 'status' => 'Sealed & Approved', 'badge_class' => 'bg-success'],
            ['pso_code' => 'PSO 2', 'business_date' => '2026-08-11', 'created_date_formatted' => '11-Aug-2026', 'days_remaining' => 4, 'total_amount' => 242000, 'status' => 'Audit Cleared', 'badge_class' => 'bg-info text-white'],
            ['pso_code' => 'PSO 1', 'business_date' => '2026-08-10', 'created_date_formatted' => '10-Aug-2026', 'days_remaining' => 3, 'total_amount' => 195000, 'status' => 'Sealed & Approved', 'badge_class' => 'bg-success'],
            ['pso_code' => 'PSO 3', 'business_date' => '2026-08-09', 'created_date_formatted' => '09-Aug-2026', 'days_remaining' => 2, 'total_amount' => 182000, 'status' => 'Sealed & Approved', 'badge_class' => 'bg-success'],
            ['pso_code' => 'PSO 1', 'business_date' => '2026-08-08', 'created_date_formatted' => '08-Aug-2026', 'days_remaining' => 1, 'total_amount' => 204000, 'status' => 'Auto-Archived', 'badge_class' => 'bg-secondary'],
        ];

        foreach ($retentions as $r) {
            PsoRetention::create($r);
        }

        // 9. System Settings
        SystemSetting::setVal('business_date', '2026-08-14', 'Current Active ERP Business Accounting Date');
        SystemSetting::setVal('cutoff_time', '19:00', 'Daily PSO Cutoff Time (24h IST)');
        SystemSetting::setVal('cutoff_rollover_active', '1', 'Automatic Next-Day PSO Rollover Toggle');

        // 10. Audit Trail
        AuditLog::create([
            'user_name' => 'Ramesh Sharma',
            'action' => 'EXCEL_IMPORT',
            'details' => 'Imported Tally_DayBook_14Aug2026.xlsx with 32 records total ₹7,00,000',
            'created_at' => '2026-08-14 18:45:10'
        ]);

        AuditLog::create([
            'user_name' => 'Ramesh Sharma',
            'action' => 'VERIFY_SEQUENCE',
            'details' => 'Sequence verification ran. Identified Missing bill CB 02 (₹17,500)',
            'created_at' => '2026-08-14 18:48:22'
        ]);

        AuditLog::create([
            'user_name' => 'Pooja Verma',
            'action' => 'RECON_CHECK',
            'details' => 'Reconciliation evaluated: Difference of ₹17,500 detected. Approval blocked.',
            'created_at' => '2026-08-14 18:50:00'
        ]);
    }
}
