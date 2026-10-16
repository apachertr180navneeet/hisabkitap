<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Bill;
use App\Models\PsoConfig;
use App\Models\SystemSetting;

class BillVerificationDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'Super Admin',
            'is_active' => true,
            'can_edit_bills' => true,
            'can_import_excel' => true,
            'can_manage_pso' => true,
        ]);

        SystemSetting::setVal('business_date', '2026-10-06');
    }

    public function test_pso_dropdown_filters_according_to_selected_date(): void
    {
        // PSO-1 belongs to 2026-10-06
        PsoConfig::create([
            'code' => 'PSO-1',
            'business_date' => '2026-10-06',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator One',
            'is_active' => true,
        ]);

        // PSO-2 belongs to 2026-10-05
        PsoConfig::create([
            'code' => 'PSO-2',
            'business_date' => '2026-10-05',
            'prefix' => 'RB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator Two',
            'is_active' => true,
        ]);

        // Bills for 2026-10-06
        Bill::create([
            'bill_no' => 'CB 01',
            'pso_code' => 'PSO-1',
            'business_date' => '2026-10-06',
            'bill_time' => '10:00',
            'customer_name' => 'Customer A',
            'amount' => 500,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        // Bill for 2026-10-05
        Bill::create([
            'bill_no' => 'RB 01',
            'pso_code' => 'PSO-2',
            'business_date' => '2026-10-05',
            'bill_time' => '10:00',
            'customer_name' => 'Customer B',
            'amount' => 700,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        // 1. Visit verification page for 2026-10-06: should see PSO-1, should NOT see PSO-2
        $response = $this->actingAs($this->adminUser)->get('/admin/verification?date=2026-10-06');
        $response->assertStatus(200);
        $response->assertSee('PSO-1');
        $response->assertDontSee('PSO-2');

        // 2. Visit verification page for 2026-10-05: should see PSO-2, should NOT see PSO-1
        $response = $this->actingAs($this->adminUser)->get('/admin/verification?date=2026-10-05');
        $response->assertStatus(200);
        $response->assertSee('PSO-2');
        $response->assertDontSee('PSO-1');

        // 3. Visit verification page for ALL dates: should see both PSO-1 and PSO-2
        $response = $this->actingAs($this->adminUser)->get('/admin/verification?date=ALL');
        $response->assertStatus(200);
        $response->assertSee('PSO-1');
        $response->assertSee('PSO-2');
    }

    public function test_imported_bill_pso_is_included_for_that_date(): void
    {
        // An imported PSO without PsoConfig record on 2026-10-06
        Bill::create([
            'bill_no' => 'IMP 01',
            'pso_code' => 'PSO-IMPORTED',
            'business_date' => '2026-10-06',
            'bill_time' => '10:00',
            'customer_name' => 'Customer C',
            'amount' => 1200,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/verification?date=2026-10-06');
        $response->assertStatus(200);
        $response->assertSee('PSO-IMPORTED');

        // On another date, PSO-IMPORTED should not show
        $response = $this->actingAs($this->adminUser)->get('/admin/verification?date=2026-10-05');
        $response->assertStatus(200);
        $response->assertDontSee('PSO-IMPORTED');
    }

    public function test_selected_pso_resets_if_not_belonging_to_date(): void
    {
        PsoConfig::create([
            'code' => 'PSO-1',
            'business_date' => '2026-10-06',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator One',
            'is_active' => true,
        ]);

        Bill::create([
            'bill_no' => 'CB 01',
            'pso_code' => 'PSO-1',
            'business_date' => '2026-10-06',
            'bill_time' => '10:00',
            'customer_name' => 'Customer A',
            'amount' => 500,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        // Request with date=2026-10-06 and pso=PSO-NONEXISTENT
        $response = $this->actingAs($this->adminUser)->get('/admin/verification?date=2026-10-06&pso=PSO-NONEXISTENT');
        $response->assertStatus(200);
        // The bill should still be visible because selectedPso gracefully resets to ALL
        $response->assertSee('CB 01');
    }
}
