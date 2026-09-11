<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class RoleAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_gates_and_user_permission_helpers(): void
    {
        $superAdmin = User::where('code', 'usr_admin')->first();
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->hasPermission('can_manage_users'));
        $this->assertTrue($superAdmin->hasPermission('can_approve_sealing'));
        $this->assertTrue($superAdmin->hasPermission('can_configure_pso'));
        $this->assertTrue($superAdmin->hasPermission('can_manage_prefixes'));
        $this->assertTrue($superAdmin->hasPermission('can_manage_salespersons'));
        $this->assertTrue($superAdmin->hasPermission('can_create_pso'));
        $this->assertTrue($superAdmin->hasPermission('can_edit_pso'));
        $this->assertTrue($superAdmin->hasPermission('can_delete_pso'));
        $this->assertTrue($superAdmin->hasPermission('can_close_pso'));
        $this->assertTrue($superAdmin->hasPermission('can_edit_bills'));
        $this->assertTrue($superAdmin->hasPermission('can_import_excel'));
        $this->assertTrue($superAdmin->hasPermission('can_record_corrections'));
        $this->assertTrue($superAdmin->hasPermission('can_record_credit'));
        $this->assertTrue($superAdmin->hasPermission('can_edit_cutoff'));

        $this->actingAs($superAdmin);
        $this->assertTrue(Gate::allows('can_manage_users'));
        $this->assertTrue(Gate::allows('can_approve_sealing'));
        $this->assertTrue(Gate::allows('can_configure_pso'));
        $this->assertTrue(Gate::allows('can_manage_prefixes'));
        $this->assertTrue(Gate::allows('can_manage_salespersons'));
        $this->assertTrue(Gate::allows('can_create_pso'));
        $this->assertTrue(Gate::allows('can_edit_pso'));
        $this->assertTrue(Gate::allows('can_delete_pso'));
        $this->assertTrue(Gate::allows('can_close_pso'));
        $this->assertTrue(Gate::allows('can_edit_bills'));
        $this->assertTrue(Gate::allows('can_import_excel'));
        $this->assertTrue(Gate::allows('can_record_corrections'));
        $this->assertTrue(Gate::allows('can_record_credit'));
        $this->assertTrue(Gate::allows('can_edit_cutoff'));

        // Operator
        $operator = User::create([
            'code' => 'usr_op_test',
            'name' => 'Operator Test',
            'email' => 'op_test@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'can_configure_pso' => true,
            'can_import_excel' => true,
            'can_edit_bills' => true,
            'can_record_corrections' => true,
            'can_record_credit' => true,
            'can_approve_sealing' => false,
            'can_edit_cutoff' => false,
            'can_manage_users' => false,
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($operator);
        $this->assertTrue($operator->hasPermission('can_configure_pso'));
        $this->assertFalse($operator->hasPermission('can_manage_users'));
        $this->assertFalse($operator->hasPermission('can_approve_sealing'));
        $this->assertTrue(Gate::allows('can_configure_pso'));
        $this->assertFalse(Gate::allows('can_manage_users'));
        $this->assertFalse(Gate::allows('can_approve_sealing'));
    }

    public function test_unauthorized_user_blocked_from_user_management(): void
    {
        $operator = User::create([
            'code' => 'usr_op_test2',
            'name' => 'Operator Test 2',
            'email' => 'op_test2@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'can_manage_users' => false,
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($operator);

        $response = $this->get('/admin/users');
        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('error');

        $storeRes = $this->post('/admin/users/store', [
            'name' => 'Hacker User',
            'email' => 'hacker@hisabkitap.in',
            'password' => 'password123',
            'role_code' => 'SUPER_ADMIN',
        ]);
        $storeRes->assertRedirect('/admin/dashboard');
        $storeRes->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'hacker@hisabkitap.in']);
    }

    public function test_unauthorized_user_blocked_from_approval_sealing(): void
    {
        $operator = User::create([
            'code' => 'usr_op_test3',
            'name' => 'Operator Test 3',
            'email' => 'op_test3@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'can_approve_sealing' => false,
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($operator);

        $sealRes = $this->post('/admin/approval-sealing/seal');
        $sealRes->assertRedirect('/admin/dashboard');
        $sealRes->assertSessionHas('error');

        $unsealRes = $this->post('/admin/approval-sealing/unseal');
        $unsealRes->assertRedirect('/admin/dashboard');
        $unsealRes->assertSessionHas('error');
    }

    public function test_switch_user_functionality(): void
    {
        $superAdmin = User::where('code', 'usr_admin')->first();
        $operator = User::create([
            'code' => 'usr_op_switch',
            'name' => 'Switchable Operator',
            'email' => 'op_switch@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get("/admin/switch-user/{$operator->id}");
        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('success');
        $this->assertEquals($operator->id, auth()->id());
    }

    public function test_pso_operator_can_only_access_dashboard_and_pso_list_and_create(): void
    {
        $operator = User::create([
            'code' => 'usr_pso_op',
            'name' => 'PSO Operator Only',
            'email' => 'pso_op@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'PSO Operator',
            'role_code' => 'OPERATOR',
            'can_configure_pso' => true,
            'can_create_pso' => true,
            'can_edit_pso' => false,
            'can_delete_pso' => false,
            'can_close_pso' => true,
            'can_manage_prefixes' => false,
            'can_manage_salespersons' => false,
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($operator);

        // 1. Can access Dashboard
        $dashRes = $this->get('/admin/dashboard');
        $dashRes->assertStatus(200);

        // 2. Can access PSO Index (List View)
        $psoListRes = $this->get('/admin/pso');
        $psoListRes->assertStatus(200);

        // 3. Can access PSO Create form
        $psoCreateRes = $this->get('/admin/pso/create');
        $psoCreateRes->assertStatus(200);

        // 4. Can submit/store new PSO
        $psoStoreRes = $this->post('/admin/pso/store', [
            'prefix' => 'CB',
            'operator_name' => 'PSO Operator Only',
            'start_no' => 1,
            'end_no' => 10,
        ]);
        $psoStoreRes->assertRedirect('/admin/pso');
        $this->assertDatabaseHas('pso_configs', ['operator_name' => 'PSO Operator Only']);

        // 4b. Now that a PSO exists, verify 'Close PSO' button is rendered for operator
        $psoListAfterRes = $this->get('/admin/pso');
        $psoListAfterRes->assertStatus(200);
        $psoListAfterRes->assertSee('Close PSO');

        $pso = \App\Models\PsoConfig::where('operator_name', 'PSO Operator Only')->first();
        $this->assertEquals($operator->id, $pso->created_by);
        $this->assertEquals($operator->name, $pso->created_by_name);

        // 4c. Operator closes PSO with Goods Return
        $closeRes = $this->post("/admin/pso/{$pso->id}/close", [
            'has_goods_return' => '1',
            'goods_return_amount' => '1250.00',
            'goods_return_bill_no' => 'CB 05',
            'goods_return_particulars' => 'Customer returned defective item',
        ]);
        $closeRes->assertRedirect('/admin/pso');
        $pso->refresh();
        $this->assertTrue($pso->is_closed);
        $this->assertTrue($pso->has_goods_return);
        $this->assertEquals(1250.00, (float)$pso->goods_return_amount);
        $this->assertEquals($operator->id, $pso->closed_by);
        $this->assertEquals($operator->name, $pso->closed_by_name);

        // 4d. Verify table shows 'PSO Closed' and return amount
        $closedListRes = $this->get('/admin/pso');
        $closedListRes->assertSee('PSO Closed');
        $closedListRes->assertSee('1,250.00');

        // 5. BLOCKED from PSO Edit view
        $psoEditRes = $this->get("/admin/pso/{$pso->id}/edit");
        $psoEditRes->assertRedirect('/admin/dashboard');
        $psoEditRes->assertSessionHas('error');

        // 6. BLOCKED from PSO Update
        $psoUpdateRes = $this->post("/admin/pso/{$pso->id}/update", [
            'operator_name' => 'Hacked Name',
        ]);
        $psoUpdateRes->assertRedirect('/admin/dashboard');
        $psoUpdateRes->assertSessionHas('error');

        // 7. BLOCKED from PSO Toggle
        $psoToggleRes = $this->post("/admin/pso/{$pso->id}/toggle");
        $psoToggleRes->assertRedirect('/admin/dashboard');
        $psoToggleRes->assertSessionHas('error');

        // 8. BLOCKED from PSO Delete
        $psoDelRes = $this->delete("/admin/pso/{$pso->id}");
        $psoDelRes->assertRedirect('/admin/dashboard');
        $psoDelRes->assertSessionHas('error');

        // 9. BLOCKED from other modules
        $blockedRoutes = [
            '/admin/import',
            '/admin/verification',
            '/admin/cash-denomination',
            '/admin/payment-classification',
            '/admin/corrections',
            '/admin/credit-collection',
            '/admin/pso-summary',
            '/admin/reconciliation',
            '/admin/approval-sealing',
            '/admin/retention',
            '/admin/reports',
            '/admin/settings',
            '/admin/users',
            '/admin/prefix-master',
            '/admin/salespersons',
        ];

        foreach ($blockedRoutes as $route) {
            $res = $this->get($route);
            $res->assertRedirect('/admin/dashboard');
            $res->assertSessionHas('error');
        }
    }

    public function test_user_with_permissions_can_manage_pso_crud_prefixes_and_salespersons(): void
    {
        $customUser = User::create([
            'code' => 'usr_full_mgr',
            'name' => 'Full PSO Manager',
            'email' => 'full_mgr@hisabkitap.in',
            'password' => Hash::make('password'),
            'role_name' => 'Operations Manager',
            'role_code' => 'OPS_MANAGER',
            'can_configure_pso' => true,
            'can_create_pso' => true,
            'can_edit_pso' => true,
            'can_delete_pso' => true,
            'can_close_pso' => true,
            'can_manage_prefixes' => true,
            'can_manage_salespersons' => true,
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($customUser);

        // 1. Can access Prefix Master and create prefix
        $prefixRes = $this->get('/admin/prefix-master');
        $prefixRes->assertStatus(200);

        $storePrefixRes = $this->post('/admin/prefix-master/store', [
            'prefix' => 'FULL',
            'name' => 'Full Series',
        ]);
        $storePrefixRes->assertRedirect('/admin/prefix-master');
        $this->assertDatabaseHas('prefixes', ['prefix' => 'FULL']);

        // 2. Can access Salespersons and create salesperson
        $spRes = $this->get('/admin/salespersons');
        $spRes->assertStatus(200);

        $storeSpRes = $this->post('/admin/salespersons/store', [
            'name' => 'Salesman Rajesh',
            'phone' => '9876543210',
        ]);
        $storeSpRes->assertRedirect('/admin/salespersons');
        $this->assertDatabaseHas('salespersons', ['name' => 'Salesman Rajesh']);

        // 3. Can create, edit, close, and delete PSO
        $psoStoreRes = $this->post('/admin/pso/store', [
            'prefix' => 'FULL',
            'operator_name' => 'Full PSO Operator',
            'start_no' => 1,
            'end_no' => 10,
        ]);
        $psoStoreRes->assertRedirect('/admin/pso');
        $pso = \App\Models\PsoConfig::where('operator_name', 'Full PSO Operator')->first();
        $this->assertNotNull($pso);

        // Edit PSO
        $psoEditPage = $this->get("/admin/pso/{$pso->id}/edit");
        $psoEditPage->assertStatus(200);

        $psoUpdateRes = $this->post("/admin/pso/{$pso->id}/update", [
            'prefix' => 'FULL',
            'operator_name' => 'Full PSO Operator Updated',
            'start_no' => 1,
            'end_no' => 15,
        ]);
        $psoUpdateRes->assertRedirect('/admin/pso');
        $pso->refresh();
        $this->assertEquals(15, $pso->end_no);

        // Delete PSO
        $psoDelRes = $this->delete("/admin/pso/{$pso->id}");
        $psoDelRes->assertRedirect('/admin/pso');
        $this->assertDatabaseMissing('pso_configs', ['id' => $pso->id]);
    }
}
