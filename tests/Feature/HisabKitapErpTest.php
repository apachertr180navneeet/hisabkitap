<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\HisabKitapDatabaseSeeder;
use App\Models\User;
use App\Models\PsoConfig;
use App\Models\Bill;

class HisabKitapErpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HisabKitapDatabaseSeeder::class);
    }

    public function test_super_admin_user_is_only_user_in_fresh_database(): void
    {
        $this->assertEquals(1, User::count());
        $admin = User::first();
        $this->assertEquals('admin@hisabkitap.in', $admin->email);
        $this->assertEquals('ADMIN', $admin->role_code);
    }

    public function test_public_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('HisabKitap ERP');
        $response->assertSee('Zero Discrepancy', false);
        $response->assertSee('Admin Login');
    }

    public function test_admin_dashboard_loads_clean_empty_state(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('HisabKitap ERP');
        $response->assertSee('No active PSO counter series configured', false);

        // Verify alias /admin/dashoard
        $aliasResponse = $this->get('/admin/dashoard');
        $aliasResponse->assertStatus(200);
    }

    public function test_pso_management_can_configure_new_series(): void
    {
        $response = $this->get('/admin/pso');
        $response->assertStatus(200);
        $response->assertSee('PSO Series Management');

        // Create a new PSO
        $createRes = $this->post('/admin/pso/store', [
            'code' => 'PSO-1',
            'name' => 'PSO 1 - Main Wholesale Counter',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Ramesh Sharma',
        ]);
        $createRes->assertRedirect('/admin/pso');
        $this->assertEquals(1, PsoConfig::count());
    }

    public function test_bill_verification_and_resolution_flow(): void
    {
        // Seed a sample bill
        Bill::create([
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
            'status' => 'Missing',
            'is_post_cutoff' => false,
            'is_special' => false,
        ]);

        $response = $this->get('/admin/verification');
        $response->assertStatus(200);
        $response->assertSee('CB 01');

        // Resolve missing bill CB 01
        $res = $this->postJson('/admin/verification/resolve-missing', [
            'bill_no' => 'CB 01',
            'reason' => 'Physical Slip Found & Verified in Counter Bundle',
            'remark' => 'Verified counter slip'
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        // Check bill is now Matched
        $this->assertEquals('Matched', Bill::first()->status);
    }

    public function test_clean_login_page_loads(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Sign In to Dashboard', false);
        $response->assertSee('Work Email Address', false);
        $response->assertSee('Authenticate & Open ERP', false);
    }

    public function test_credential_login_and_logout_flow(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@hisabkitap.in',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();

        $logoutResponse = $this->get('/admin/logout');
        $logoutResponse->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_profile_page_loads_and_change_password(): void
    {
        // Login as Super Admin
        $this->post('/admin/login', [
            'email' => 'admin@hisabkitap.in',
            'password' => 'password',
        ]);

        $response = $this->get('/admin/profile');
        $response->assertStatus(200);
        $response->assertSee('My Account Profile', false);
        $response->assertSee('Change Account Password', false);

        // Update profile
        $updateRes = $this->post('/admin/profile/update', [
            'name' => 'Suresh Gupta (Lead Admin)',
            'email' => 'admin@hisabkitap.in',
        ]);
        $updateRes->assertRedirect('/admin/profile');

        // Change password
        $passRes = $this->post('/admin/profile/change-password', [
            'current_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);
        $passRes->assertRedirect('/admin/profile');
        $passRes->assertSessionHas('success');
    }
}
