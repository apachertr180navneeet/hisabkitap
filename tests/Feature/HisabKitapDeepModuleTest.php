<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Correction;
use App\Models\CreditCollection;
use App\Models\PsoConfig;
use App\Models\PsoDailySeal;
use App\Models\PsoRetention;
use App\Models\SystemSetting;
use App\Models\TallyImport;
use App\Models\User;
use App\Services\ReconciliationService;
use Database\Seeders\HisabKitapDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HisabKitapDeepModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HisabKitapDatabaseSeeder::class);
        $this->loginAsAdmin();
    }

    protected function loginAsAdmin(): void
    {
        $this->post('/admin/login', [
            'email' => 'admin@hisabkitap.in',
            'password' => 'password',
        ]);
    }

    protected function makePso(string $code = 'PSO-1', string $prefix = 'CB'): PsoConfig
    {
        return PsoConfig::create([
            'code' => $code,
            'name' => "{$code} Test Counter",
            'prefix' => $prefix,
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Ramesh Sharma',
            'is_active' => true,
        ]);
    }

    protected function makeBill(array $overrides = []): Bill
    {
        $defaults = [
            'bill_no' => 'CB 01',
            'pso_code' => 'PSO-1',
            'business_date' => date('Y-m-d'),
            'bill_time' => '10:30',
            'customer_name' => 'Vijay Fuels',
            'amount' => 10000,
            'payment_type' => 'Cash',
            'cd_amount' => 0,
            'refund_amount' => 0,
            'net_amount' => 10000,
            'status' => 'Matched',
            'is_post_cutoff' => false,
        ];

        return Bill::create(array_merge($defaults, $overrides));
    }

    public function test_tally_import_page_loads(): void
    {
        $response = $this->get('/admin/import');
        $response->assertStatus(200);
        $response->assertSee('Tally');
    }

    public function test_tally_import_without_file_creates_mock_record(): void
    {
        $response = $this->post('/admin/import', [
            'business_date' => '2026-08-14',
            'pso_id' => 'PSO-1',
        ]);
        $response->assertStatus(302);
        $this->assertEquals(1, TallyImport::count());
        $import = TallyImport::first();
        $this->assertEquals('Imported & Scanned', $import->status);
        $this->assertEquals('2026-08-14', $import->business_date->format('Y-m-d'));
        $this->assertEquals(32, $import->total_records);
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXCEL_IMPORT']);
    }

    public function test_tally_import_with_uploaded_file(): void
    {
        $this->makePso('PSO-1', 'CB');
        $csvContent = "Date,Particulars,Voucher Type,Voucher No.,Amount\n"
                    . "14/08/2026,Customer One [101],Sales Cadbury,CB 01,5000.00\n"
                    . "14/08/2026,Customer Two [102],Credit,CB 02,2500.00\n";

        $file = UploadedFile::fake()->createWithContent('Tally_2026.csv', $csvContent);
        $response = $this->post('/admin/import', [
            'business_date' => '2026-08-14',
            'pso_id' => 'PSO-1',
            'excel_file' => $file,
        ]);
        $response->assertRedirect('/admin/verification');
        $this->assertEquals(1, TallyImport::count());
        $this->assertEquals('Tally_2026.csv', TallyImport::first()->filename);
        $this->assertEquals(2, TallyImport::first()->total_records);
        $this->assertEquals(7500.0, (float) TallyImport::first()->total_amount);
        $this->assertEquals(2, Bill::count());
    }

    public function test_tally_import_rejects_invalid_headers(): void
    {
        $csvContent = "ColA,ColB\n1,2\n";
        $file = UploadedFile::fake()->createWithContent('Invalid_Header.csv', $csvContent);
        $response = $this->post('/admin/import', [
            'business_date' => '2026-08-14',
            'pso_id' => 'PSO-1',
            'excel_file' => $file,
        ]);
        $response->assertSessionHas('error');
        $this->assertEquals(0, TallyImport::count());
    }

    public function test_tally_import_reports_and_skips_invalid_amount_rows(): void
    {
        $this->makePso('PSO-1', 'CB');
        $csvContent = "Date,Particulars,Voucher Type,Voucher No.,Amount\n"
                    . "14/08/2026,Valid Customer,Sales,CB 01,5000.00\n"
                    . "14/08/2026,Corrupt Customer,Sales,CB 02,NOT_A_NUMBER\n";

        $file = UploadedFile::fake()->createWithContent('Partial_Invalid.csv', $csvContent);
        $response = $this->post('/admin/import', [
            'business_date' => '2026-08-14',
            'pso_id' => 'PSO-1',
            'excel_file' => $file,
        ]);
        $response->assertRedirect('/admin/verification');
        $response->assertSessionHas('warning');
        $response->assertSessionHas('import_errors');
        $this->assertEquals(1, Bill::count());
    }

    public function test_import_sample_download_returns_csv(): void
    {
        $response = $this->get('/admin/import/sample-download?format=csv');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_import_sample_download_returns_xls(): void
    {
        $response = $this->get('/admin/import/sample-download?format=xls');
        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xls', $response->headers->get('Content-Disposition'));
    }

    public function test_payment_classification_page_loads_and_filters(): void
    {
        $this->makePso();
        $this->makeBill();
        $bill = Bill::first();
        $bill->update(['payment_type' => 'Paytm', 'net_amount' => 9500]);

        $response = $this->get('/admin/payment-classification?paytype=Paytm');
        $response->assertStatus(200);
        $response->assertSee('Paytm');
    }

    public function test_corrections_store_updates_bill_and_sums(): void
    {
        $this->makePso();
        $bill = $this->makeBill(['amount' => 10000, 'net_amount' => 10000]);

        $response = $this->post('/admin/corrections/store', [
            'bill_no' => 'CB 01',
            'correction_type' => 'Cash Discount',
            'cd_amount' => 500,
            'goods_return_amount' => 200,
            'refund_amount' => 100,
            'reason' => 'Customer discount approved',
        ]);
        $response->assertStatus(302);
        $this->assertEquals(1, Correction::count());

        $corr = Correction::first();
        $this->assertEquals('CORR-01', $corr->corr_code);
        $this->assertEquals(-800.0, (float) $corr->net_adjustment);

        $bill->refresh();
        $this->assertEqualsWithDelta(500, (float) $bill->cd_amount, 0.01);
        $this->assertEqualsWithDelta(300, (float) $bill->refund_amount, 0.01);
        $this->assertEqualsWithDelta(9200, (float) $bill->net_amount, 0.01);

        $this->assertDatabaseHas('audit_logs', ['action' => 'CORRECTION_ADDED']);
    }

    public function test_credit_collection_page_and_payment_update(): void
    {
        CreditCollection::create([
            'bill_no' => 'CB 01',
            'customer_name' => 'Vijay Fuels',
            'salesman_name' => 'Suresh',
            'bill_date' => date('Y-m-d'),
            'bill_amount' => 20000,
            'paid_amount' => 5000,
            'outstanding_amount' => 15000,
            'collection_status' => 'Partially Collected',
        ]);

        $response = $this->get('/admin/credit-collection');
        $response->assertStatus(200);
        $response->assertSee('Vijay Fuels');

        $res = $this->post('/admin/credit-collection/update', [
            'credit_id' => 1,
            'paid_today' => 15000,
            'payment_mode' => 'Check',
            'remark' => 'Final settlement',
        ]);
        $res->assertStatus(302);

        $credit = CreditCollection::first();
        $this->assertEqualsWithDelta(20000, (float) $credit->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $credit->outstanding_amount, 0.01);
        $this->assertEquals('Collected', $credit->collection_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREDIT_RECOVERY']);
    }

    public function test_credit_payment_clamps_to_bill_amount(): void
    {
        CreditCollection::create([
            'bill_no' => 'CB 02',
            'customer_name' => 'Test Customer',
            'salesman_name' => 'Suresh',
            'bill_date' => date('Y-m-d'),
            'bill_amount' => 10000,
            'paid_amount' => 0,
            'outstanding_amount' => 10000,
            'collection_status' => 'Pending',
        ]);

        $this->post('/admin/credit-collection/update', [
            'credit_id' => 1,
            'paid_today' => 99999,
            'payment_mode' => 'Cash',
        ]);

        $credit = CreditCollection::first();
        $this->assertEqualsWithDelta(10000, (float) $credit->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $credit->outstanding_amount, 0.01);
        $this->assertEquals('Collected', $credit->collection_status);
    }

    public function test_credit_export_returns_csv(): void
    {
        CreditCollection::create([
            'bill_no' => 'CB 03',
            'customer_name' => 'Export Customer',
            'salesman_name' => 'Suresh',
            'bill_date' => date('Y-m-d'),
            'bill_amount' => 5000,
            'paid_amount' => 5000,
            'outstanding_amount' => 0,
            'collection_status' => 'Collected',
        ]);

        $response = $this->get('/admin/credit-collection/export');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_pso_summary_matrix_page_loads(): void
    {
        $this->makePso();
        $this->makeBill(['payment_type' => 'Cash', 'amount' => 10000, 'net_amount' => 10000]);
        $this->makeBill(['bill_no' => 'CB 02', 'payment_type' => 'Paytm', 'amount' => 5000, 'net_amount' => 4800]);

        $response = $this->get('/admin/pso-summary');
        $response->assertStatus(200);
        $response->assertSee('PSO-1');
    }

    public function test_reconciliation_page_loads_and_quick_resolve(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Missing']);

        $response = $this->get('/admin/reconciliation');
        $response->assertStatus(200);
        $response->assertSee('CB 01');

        $res = $this->post('/admin/reconciliation/quick-resolve');
        $res->assertStatus(302);
        $this->assertEquals('Matched', Bill::first()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'RECON_RESOLVE']);
    }

    public function test_approval_sealing_flow_full_lifecycle(): void
    {
        $this->makePso();
        // Fully reconciled day: one matched bill
        $this->makeBill(['status' => 'Matched', 'amount' => 10000, 'net_amount' => 10000]);

        // Cannot seal while there is no seal discrepancy... metrics must reconcile
        $response = $this->post('/admin/approval-sealing/seal');
        $response->assertStatus(302);
        $this->assertEquals(1, PsoDailySeal::count());

        $seal = PsoDailySeal::first();
        $this->assertTrue((bool) $seal->is_sealed);
        $this->assertEquals('Sealed & Approved', $seal->status);
        $this->assertNotNull($seal->seal_hash);
        $this->assertStringStartsWith('SHA256:', $seal->seal_hash);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SEAL_DAY']);

        $session = $this->get('/admin/approval-sealing');
        $session->assertStatus(200);

        // Unseal
        $unseal = $this->post('/admin/approval-sealing/unseal', ['reason' => 'Correction needed']);
        $unseal->assertStatus(302);
        $seal->refresh();
        $this->assertFalse((bool) $seal->is_sealed);
        $this->assertEquals('Unsealed', $seal->status);
        $this->assertEquals('Correction needed', $seal->unseal_reason);
        $this->assertDatabaseHas('audit_logs', ['action' => 'UNSEAL_DAY']);
    }

    public function test_approval_sealing_blocks_reconciliation_variance(): void
    {
        $this->makePso();
        // Create a missing bill -> not reconciled -> should block sealing
        $this->makeBill(['status' => 'Missing', 'amount' => 10000, 'net_amount' => 10000]);

        $response = $this->post('/admin/approval-sealing/seal');
        $response->assertSessionHas('error');
        $response->assertStatus(302);
        $this->assertEquals(0, PsoDailySeal::count());
    }

    public function test_retention_radar_page_loads_with_data(): void
    {
        PsoRetention::create([
            'pso_code' => 'PSO-1',
            'business_date' => date('Y-m-d'),
            'created_date_formatted' => date('Y-m-d'),
            'days_remaining' => 4,
            'total_amount' => 250000,
            'status' => 'Pending Approval',
            'badge_class' => 'bg-warning text-dark',
        ]);

        $response = $this->get('/admin/retention');
        $response->assertStatus(200);
        $response->assertSee('PSO-1');
        $response->assertSee('Pending Approval');
    }

    public function test_reports_all_types_render(): void
    {
        $this->makePso();
        $this->makeBill();

        foreach (['daily_pso', 'recon_sheet', 'credit_sheet', 'missing_bills', 'corrections_log', 'audit_history'] as $type) {
            $response = $this->get("/admin/reports?type={$type}");
            $response->assertStatus(200);
        }
    }

    public function test_reports_export_csv(): void
    {
        $this->makePso();
        $this->makeBill();

        $response = $this->get('/admin/reports/export?type=daily_pso');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_reports_export_missing_bills_export_bills_not_audit_log(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Missing', 'bill_no' => 'CB 01']);

        $response = $this->get('/admin/reports/export?type=missing_bills');
        $response->assertStatus(200);
        $content = $response->streamedContent();
        // Must contain the missing bill, not merely audit-log columns
        $this->assertStringContainsString('CB 01', $content);
        $this->assertStringContainsString('Bill No', $content);
    }

    public function test_reports_export_corrections_log_export_corrections(): void
    {
        $this->makePso();
        $bill = $this->makeBill();
        Correction::create([
            'corr_code' => 'CORR-01',
            'bill_id' => $bill->id,
            'bill_no' => 'CB 01',
            'original_amount' => 10000,
            'correction_type' => 'Cash Discount',
            'cd_amount' => 500,
            'goods_return_amount' => 0,
            'refund_amount' => 0,
            'net_adjustment' => -500,
            'reason' => 'Volume discount',
            'approved_by' => 'Pooja Verma',
        ]);

        $response = $this->get('/admin/reports/export?type=corrections_log');
        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('CORR-01', $content);
        $this->assertStringContainsString('Volume discount', $content);
    }

    public function test_reports_index_renders_missing_bills_and_corrections_preview(): void
    {
        $this->makePso();
        $bill = $this->makeBill(['status' => 'Missing']);
        Correction::create([
            'corr_code' => 'CORR-01',
            'bill_id' => $bill->id,
            'bill_no' => 'CB 01',
            'original_amount' => 10000,
            'correction_type' => 'Refund',
            'cd_amount' => 0,
            'goods_return_amount' => 0,
            'refund_amount' => 250,
            'net_adjustment' => -250,
            'reason' => 'Customer return',
            'approved_by' => 'Pooja Verma',
        ]);

        $missing = $this->get('/admin/reports?type=missing_bills');
        $missing->assertStatus(200);
        $missing->assertSee('Bill No');

        $corr = $this->get('/admin/reports?type=corrections_log');
        $corr->assertStatus(200);
        $corr->assertSee('CORR-01');
        $corr->assertSee('Pooja Verma');
    }

    public function test_reconciliation_metrics_include_dynamic_pso_codes(): void
    {
        // Create a PSO beyond the hardcoded PSO-1/2/3 set and confirm it is still counted
        $this->makePso('PSO-4', 'IB');
        Bill::create([
            'bill_no' => 'IB 01',
            'pso_code' => 'PSO-4',
            'business_date' => date('Y-m-d'),
            'customer_name' => 'Dynamic PSO Customer',
            'amount' => 25000,
            'payment_type' => 'Cash',
            'cd_amount' => 0,
            'refund_amount' => 0,
            'net_amount' => 25000,
            'status' => 'Matched',
            'is_post_cutoff' => false,
        ]);

        $service = app(ReconciliationService::class);
        $metrics = $service->getMetrics();

        // Dynamic PSO collection must include PSO-4 even though pso1/2/3 totals are 0
        $this->assertEquals(25000, $metrics['psoCollection']);
        $this->assertEquals(0, $metrics['pso1Total']);
        $this->assertTrue($metrics['isReconciled']);
    }

    public function test_settings_page_loads_and_admin_update(): void
    {
        $response = $this->get('/admin/settings');
        $response->assertStatus(200);

        $res = $this->post('/admin/settings/update', [
            'cutoff_time' => '18:30',
            'cutoff_rollover_active' => 'on',
        ]);
        $res->assertStatus(302);
        $this->assertEquals('18:30', SystemSetting::getVal('cutoff_time'));
        $this->assertEquals('1', SystemSetting::getVal('cutoff_rollover_active'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'SETTINGS_UPDATE']);
    }

    public function test_settings_allows_super_admin_role_update(): void
    {
        // A user with role_code SUPER_ADMIN must also be able to update settings
        $this->post('/admin/logout');
        User::create([
            'code' => 'usr_sa',
            'name' => 'Second Super Admin',
            'email' => 'super2@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'Super Administrator',
            'role_code' => 'SUPER_ADMIN',
            'can_edit_cutoff' => true,
            'is_active' => true,
            'is_read_only' => false,
            'allowed_modules' => ['All Modules'],
        ]);
        $this->post('/admin/login', ['email' => 'super2@hisabkitap.in', 'password' => 'password']);

        $res = $this->post('/admin/settings/update', ['cutoff_time' => '17:45']);
        $res->assertStatus(302);
        $res->assertSessionHas('success');
        $this->assertEquals('17:45', SystemSetting::getVal('cutoff_time'));
    }

    public function test_settings_denies_non_admin_update(): void
    {
        // Create a non-admin user and login
        $this->post('/admin/logout');
        $operator = User::create([
            'code' => 'usr_op',
            'name' => 'Operator One',
            'email' => 'op@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'badge_color' => 'primary',
            'badge_class' => 'bg-primary',
            'avatar' => 'OP',
            'icon' => 'bi-person-badge-fill',
            'title' => 'Counter Operator',
            'tagline' => 'Ops',
            'is_active' => true,
            'is_read_only' => false,
            'allowed_modules' => ['Dashboard'],
        ]);
        $this->post('/admin/login', ['email' => 'op@hisabkitap.in', 'password' => 'password']);

        $res = $this->post('/admin/settings/update', ['cutoff_time' => '20:00']);
        $res->assertSessionHas('error');
        $this->assertNotEquals('20:00', SystemSetting::getVal('cutoff_time'));
    }

    public function test_dashboard_shows_pso_rows_and_metrics(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Missing']);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Missing Bills Detected');
    }

    public function test_landing_page_with_pso_and_metrics(): void
    {
        $this->makePso();
        $this->makeBill();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('HisabKitap ERP');
    }

    public function test_admin_routes_redirect_guests_to_login(): void
    {
        $this->post('/admin/logout');
        $res = $this->get('/admin/profile');
        $res->assertStatus(302);
        $res->assertRedirect('/login');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->post('/admin/logout');
        User::create([
            'code' => 'usr_inactive',
            'name' => 'Inactive User',
            'email' => 'inactive@hisabkitap.in',
            'password' => Hash::make('secret123'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'is_active' => false,
            'is_read_only' => false,
            'allowed_modules' => ['Dashboard'],
        ]);

        $res = $this->post('/admin/login', ['email' => 'inactive@hisabkitap.in', 'password' => 'secret123']);
        $res->assertStatus(302);
        $res->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_read_only_user_cannot_perform_write_actions(): void
    {
        $this->post('/admin/logout');
        User::create([
            'code' => 'usr_ro',
            'name' => 'Read Only Auditor',
            'email' => 'auditor@hisabkitap.in',
            'password' => Hash::make('secret123'),
            'role_name' => 'Accounts Approver',
            'role_code' => 'APPROVER',
            'is_active' => true,
            'is_read_only' => true,
            'can_approve_sealing' => true,
            'allowed_modules' => ['Approval & Sealing'],
        ]);
        $this->post('/admin/login', ['email' => 'auditor@hisabkitap.in', 'password' => 'secret123']);

        $this->makePso();
        $this->makeBill(['status' => 'Missing']);

        // Attempt a write action - should be blocked and no state change
        $res = $this->post('/admin/verification/auto-verify');
        $res->assertSessionHas('error');
        $this->assertEquals('Missing', Bill::first()->status);
    }

    public function test_read_only_user_can_still_view_pages(): void
    {
        $this->post('/admin/logout');
        User::create([
            'code' => 'usr_ro2',
            'name' => 'Read Only Viewer',
            'email' => 'viewer@hisabkitap.in',
            'password' => Hash::make('secret123'),
            'role_name' => 'Accounts Approver',
            'role_code' => 'APPROVER',
            'is_active' => true,
            'is_read_only' => true,
            'allowed_modules' => ['Dashboard'],
        ]);
        $this->post('/admin/login', ['email' => 'viewer@hisabkitap.in', 'password' => 'secret123']);

        $res = $this->get('/admin/dashboard');
        $res->assertStatus(200);
    }

    public function test_pso_toggle_status_and_validation(): void
    {
        $pso = $this->makePso();

        // Invalid store (missing required fields)
        $res = $this->post('/admin/pso/store', ['name' => 'X']);
        $res->assertStatus(302);
        $res->assertSessionHasErrors();
        $this->assertEquals(1, PsoConfig::count());

        // Valid store
        $res = $this->post('/admin/pso/store', [
            'code' => 'ignored',
            'name' => 'Second Counter',
            'prefix' => 'CB2',
            'start_no' => 1,
            'end_no' => 20,
            'operator_name' => 'Priya',
            'specials' => 'ITC 01, ITC 03',
        ]);
        $res->assertStatus(302);
        $this->assertEquals(2, PsoConfig::count());
        $second = PsoConfig::where('code', 'PSO-2')->first();
        $this->assertNotNull($second);
        $this->assertEquals(['ITC 01', 'ITC 03'], $second->specials);
        $this->assertEquals('CB2', $second->prefix);

        // Toggle status
        $res = $this->post("/admin/pso/{$pso->id}/toggle");
        $res->assertStatus(302);
        $pso->refresh();
        $this->assertFalse((bool) $pso->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PSO_STATUS_TOGGLE']);
    }

    public function test_pso_code_generation_avoids_duplicates_after_deletion(): void
    {
        // Simulate PSO-1, PSO-2, PSO-3 existing, then PSO-2 deleted (no delete route, so direct DB)
        $p1 = $this->makePso('PSO-1', 'CB');
        $p2 = $this->makePso('PSO-2', 'CB');
        $this->makePso('PSO-3', 'CB');
        $p2->delete();

        // Next created code must not collide with existing PSO-1/PSO-3
        $res = $this->post('/admin/pso/store', [
            'name' => 'After Deletion Counter',
            'prefix' => 'AB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Kavita',
        ]);
        $res->assertStatus(302);

        $codes = PsoConfig::pluck('code');
        $this->assertCount($codes->unique()->count(), $codes, 'PSO codes must be unique');
        $this->assertTrue($codes->contains('PSO-4'));
        $this->assertSame(PsoConfig::count(), PsoConfig::distinct('code')->count());
    }

    public function test_correction_code_generation_avoids_duplicates(): void
    {
        $this->makePso();
        $bill = $this->makeBill();

        Correction::create([
            'corr_code' => 'CORR-01',
            'bill_id' => $bill->id,
            'bill_no' => 'CB 01',
            'original_amount' => 10000,
            'correction_type' => 'Refund',
            'cd_amount' => 0,
            'goods_return_amount' => 0,
            'refund_amount' => 100,
            'net_adjustment' => -100,
            'reason' => 'First refund',
            'approved_by' => 'Pooja Verma',
        ]);

        // Second correction code must be CORR-02 (derived from max id, not count)
        $res = $this->post('/admin/corrections/store', [
            'bill_no' => 'CB 01',
            'correction_type' => 'Refund',
            'refund_amount' => 50,
            'reason' => 'Second refund',
        ]);
        $res->assertStatus(302);

        $codes = Correction::pluck('corr_code');
        $this->assertCount($codes->unique()->count(), $codes, 'Correction codes must be unique');
        $this->assertTrue($codes->contains('CORR-02'));
    }

    public function test_user_cannot_toggle_or_delete_self(): void
    {
        $admin = User::where('email', 'admin@hisabkitap.in')->first();

        // Cannot deactivate self
        $res = $this->post("/admin/users/{$admin->id}/toggle-status");
        $res->assertSessionHas('error');
        $admin->refresh();
        $this->assertTrue((bool) $admin->is_active);

        // Cannot delete self
        $res = $this->delete("/admin/users/{$admin->id}");
        $res->assertSessionHas('error');
        $this->assertEquals(1, User::count());
    }

    public function test_bill_verification_export_csv(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Matched']);
        $response = $this->get('/admin/verification/export');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Bill_Verification', $response->headers->get('Content-Disposition'));
    }

    public function test_bill_verification_filters_and_search(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Missing', 'customer_name' => 'Special Customer Xyz']);
        $this->makeBill(['bill_no' => 'CB 02', 'status' => 'Matched', 'customer_name' => 'Other']);

        // Filter by status
        $res = $this->get('/admin/verification?status=Missing');
        $res->assertStatus(200);
        $res->assertSee('CB 01');
        $res->assertDontSee('Other');

        // Filter by search term
        $res = $this->get('/admin/verification?search=Xyz');
        $res->assertStatus(200);
        $res->assertSee('Special Customer Xyz');
    }

    public function test_auto_verify_all_marks_bills_matched(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Missing']);
        $this->makeBill(['bill_no' => 'CB 02', 'status' => 'Mismatch']);

        $res = $this->post('/admin/verification/auto-verify');
        $res->assertStatus(302);
        $this->assertEquals(2, Bill::where('status', 'Matched')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'AUTO_VERIFY']);
    }

    public function test_authentication_fails_with_bad_credentials(): void
    {
        $this->post('/admin/logout');
        $res = $this->post('/admin/login', ['email' => 'admin@hisabkitap.in', 'password' => 'wrong']);
        $res->assertStatus(302);
        $res->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_show_login_redirects_when_already_authenticated(): void
    {
        $res = $this->get('/admin/login');
        $res->assertRedirect('/admin/dashboard');
    }

    public function test_profile_update_and_password_change_via_controller(): void
    {
        $res = $this->post('/admin/profile/update', [
            'name' => 'Updated Name',
            'email' => 'admin@hisabkitap.in',
        ]);
        $res->assertStatus(302);
        $this->assertEquals('Updated Name', User::where('email', 'admin@hisabkitap.in')->first()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PROFILE_UPDATE']);

        // Wrong current password
        $bad = $this->post('/admin/profile/change-password', [
            'current_password' => 'wrong',
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ]);
        $bad->assertSessionHasErrors('current_password');

        // Correct password change
        $good = $this->post('/admin/profile/change-password', [
            'current_password' => 'password',
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ]);
        $good->assertSessionHas('success');
        $this->assertDatabaseHas('audit_logs', ['action' => 'PASSWORD_CHANGE']);
    }

    public function test_login_and_logout_audit_logged(): void
    {
        $this->post('/admin/logout');
        $res = $this->post('/admin/login', ['email' => 'admin@hisabkitap.in', 'password' => 'password']);
        $res->assertStatus(302);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_LOGIN']);

        $this->get('/admin/logout');
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_LOGOUT']);
    }

    public function test_reconciliation_service_metrics_accuracy(): void
    {
        $this->makePso();
        $this->makeBill(['payment_type' => 'Cash', 'amount' => 10000, 'net_amount' => 10000, 'status' => 'Matched']);
        $this->makeBill(['bill_no' => 'CB 02', 'payment_type' => 'Paytm', 'amount' => 5000, 'cd_amount' => 200, 'net_amount' => 4800, 'status' => 'Matched']);
        $this->makeBill(['bill_no' => 'CB 03', 'payment_type' => 'Check', 'amount' => 3000, 'net_amount' => 3000, 'status' => 'Missing']);

        $service = app(ReconciliationService::class);
        $metrics = $service->getMetrics();

        $this->assertEquals(18000, $metrics['tallyTotal']);
        $this->assertEquals(1, $metrics['missingCount']);
        $this->assertEquals(2, $metrics['matchedCount']);
        $this->assertEquals(14800, $metrics['psoCollection']);
        $this->assertEquals(10000, $metrics['totCash']);
        $this->assertEquals(4800, $metrics['totPaytm']);
        $this->assertFalse($metrics['isReconciled']);
    }

    public function test_corrections_store_rejects_bill_not_on_business_date(): void
    {
        $this->makePso();
        // Bill for a different business date not matching the active business date
        Bill::create([
            'bill_no' => 'CB50',
            'pso_code' => 'PSO-1',
            'business_date' => '2020-01-01',
            'customer_name' => 'Old Customer',
            'amount' => 100,
            'payment_type' => 'Cash',
            'net_amount' => 100,
            'status' => 'Matched',
            'is_post_cutoff' => false,
        ]);

        $res = $this->post('/admin/corrections/store', [
            'bill_no' => 'CB50',
            'correction_type' => 'Refund',
            'reason' => 'test',
        ]);
        $res->assertStatus(404);
        $this->assertEquals(0, Correction::count());
    }

    public function test_resolve_missing_cancelled_status(): void
    {
        $this->makePso();
        $this->makeBill(['status' => 'Missing']);

        $res = $this->postJson('/admin/verification/resolve-missing', [
            'bill_no' => 'CB 01',
            'reason' => 'Cancelled Bill',
            'remark' => 'No slip found',
        ]);
        $res->assertStatus(200);
        $this->assertEquals('Cancelled', Bill::first()->status);
    }
}
