<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Bill;
use App\Models\PsoConfig;
use App\Models\Salesperson;
use App\Models\SystemSetting;

class BillVerificationInlineEditTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@hisabkitap.test',
            'password' => bcrypt('password'),
            'role' => 'Super Admin',
            'is_active' => true,
            'can_edit_bills' => true,
            'can_import_excel' => true,
            'can_record_corrections' => true,
            'can_record_credit' => true,
            'can_manage_pso' => true,
            'can_configure_pso' => true,
            'can_manage_users' => true,
            'can_approve_sealing' => true,
            'can_view_reports' => true,
            'can_export_data' => true,
        ]);

        SystemSetting::setVal('business_date', '2026-08-14');
    }

    public function test_verification_index_loads_with_salespersons(): void
    {
        $sp = Salesperson::create([
            'code' => 'SP-01',
            'name' => 'Ramesh Gupta',
            'prefix_code' => 'CB',
            'is_active' => true,
        ]);

        $pso = PsoConfig::create([
            'code' => 'PSO-1',
            'name' => 'Counter 1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator 1',
            'is_active' => true,
        ]);

        $bill = Bill::create([
            'bill_no' => 'CB 01',
            'pso_config_id' => $pso->id,
            'pso_code' => 'PSO-1',
            'business_date' => '2026-08-14',
            'bill_time' => '10:00',
            'customer_name' => 'Customer A',
            'amount' => 1000.00,
            'payment_type' => 'Cash',
            'cd_amount' => 0,
            'refund_amount' => 0,
            'net_amount' => 1000.00,
            'status' => 'Matched',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/verification');
        $response->assertStatus(200);
        $response->assertSee('Ramesh Gupta');
        $response->assertSee('CB 01');
        $response->assertSee('selectAllBills');
        $response->assertSee('btn-save-row');
    }

    public function test_inline_update_bill_row(): void
    {
        $sp = Salesperson::create([
            'code' => 'SP-02',
            'name' => 'Suresh Verma',
            'prefix_code' => 'RB',
            'is_active' => true,
        ]);

        $pso = PsoConfig::create([
            'code' => 'PSO-2',
            'name' => 'Counter 2',
            'prefix' => 'RB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator 2',
            'is_active' => true,
        ]);

        $bill = Bill::create([
            'bill_no' => 'RB 01',
            'pso_config_id' => $pso->id,
            'pso_code' => 'PSO-2',
            'business_date' => '2026-08-14',
            'bill_time' => '11:00',
            'customer_name' => 'Customer B',
            'amount' => 1500.00,
            'payment_type' => 'Cash',
            'cd_amount' => 0,
            'refund_amount' => 0,
            'net_amount' => 1500.00,
            'status' => 'Matched',
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/admin/verification/update-row', [
            'bill_id' => $bill->id,
            'payment_type' => 'Paytm',
            'cd_amount' => 50.00,
            'refund_amount' => 20.00,
            'salesperson_id' => $sp->id,
            'salesman_name' => $sp->name,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'bill' => [
                'id' => $bill->id,
                'bill_no' => 'RB 01',
                'payment_type' => 'Paytm',
                'cd_amount' => '50.00',
                'refund_amount' => '20.00',
                'net_amount' => '1430.00',
                'salesman_name' => 'Suresh Verma',
                'salesperson_id' => $sp->id,
            ]
        ]);

        $bill->refresh();
        $this->assertEquals('Paytm', $bill->payment_type);
        $this->assertEquals(50.00, $bill->cd_amount);
        $this->assertEquals(20.00, $bill->refund_amount);
        $this->assertEquals(1430.00, $bill->net_amount);
        $this->assertEquals('Suresh Verma', $bill->salesman_name);
        $this->assertEquals($sp->id, $bill->salesperson_id);
    }

    public function test_bulk_update_bills(): void
    {
        $sp = Salesperson::create([
            'code' => 'SP-03',
            'name' => 'Vikram Singh',
            'prefix_code' => 'MB',
            'is_active' => true,
        ]);

        $pso = PsoConfig::create([
            'code' => 'PSO-3',
            'name' => 'Counter 3',
            'prefix' => 'MB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator 3',
            'is_active' => true,
        ]);

        $bill1 = Bill::create([
            'bill_no' => 'MB 01',
            'pso_config_id' => $pso->id,
            'pso_code' => 'PSO-3',
            'business_date' => '2026-08-14',
            'bill_time' => '11:00',
            'customer_name' => 'Customer 1',
            'amount' => 500.00,
            'payment_type' => 'Cash',
            'net_amount' => 500.00,
            'status' => 'Matched',
        ]);

        $bill2 = Bill::create([
            'bill_no' => 'MB 02',
            'pso_config_id' => $pso->id,
            'pso_code' => 'PSO-3',
            'business_date' => '2026-08-14',
            'bill_time' => '11:30',
            'customer_name' => 'Customer 2',
            'amount' => 800.00,
            'payment_type' => 'Cash',
            'net_amount' => 800.00,
            'status' => 'Matched',
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/admin/verification/bulk-update', [
            'bill_ids' => [$bill1->id, $bill2->id],
            'payment_type' => 'Credit',
            'salesperson_id' => $sp->id,
            'salesman_name' => $sp->name,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $bill1->refresh();
        $bill2->refresh();

        $this->assertEquals('Credit', $bill1->payment_type);
        $this->assertEquals('Vikram Singh', $bill1->salesman_name);
        $this->assertEquals('Credit', $bill2->payment_type);
        $this->assertEquals('Vikram Singh', $bill2->salesman_name);
    }
}
