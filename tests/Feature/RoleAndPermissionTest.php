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

        $this->actingAs($superAdmin);
        $this->assertTrue(Gate::allows('can_manage_users'));
        $this->assertTrue(Gate::allows('can_approve_sealing'));
        $this->assertTrue(Gate::allows('can_configure_pso'));

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
            'is_active' => true,
            'is_read_only' => false,
        ]);

        $this->actingAs($operator);

        // 1. Can access Dashboard
        $dashRes = $this->get('/admin/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Dashboard');
        $dashRes->assertSee('PSO Management');
        // Should NOT see other module links in sidebar
        $dashRes->assertDontSee('Tally Excel Import');
        $dashRes->assertDontSee('Payment Classification');

        // 2. Can access PSO list view
        $psoListRes = $this->get('/admin/pso');
        $psoListRes->assertStatus(200);
        $psoListRes->assertSee('Configure New PSO');

        // 3. Can access PSO create view
        $psoCreateRes = $this->get('/admin/pso/create');
        $psoCreateRes->assertStatus(200);

        // 4. Can store new PSO
        $psoStoreRes = $this->post('/admin/pso/store', [
            'operator_name' => 'PSO Operator Only',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
        ]);
        $psoStoreRes->assertRedirect('/admin/pso');
        $this->assertDatabaseHas('pso_configs', ['operator_name' => 'PSO Operator Only']);

        // 4b. Now that a PSO exists, verify 'List View Only' badge is rendered for operator
        $psoListAfterRes = $this->get('/admin/pso');
        $psoListAfterRes->assertStatus(200);
        $psoListAfterRes->assertSee('List View Only');

        $pso = \App\Models\PsoConfig::where('operator_name', 'PSO Operator Only')->first();
        $this->assertEquals($operator->id, $pso->created_by);
        $this->assertEquals($operator->name, $pso->created_by_name);

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
}
