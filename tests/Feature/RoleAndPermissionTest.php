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
}
