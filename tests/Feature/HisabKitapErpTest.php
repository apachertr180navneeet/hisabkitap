<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\HisabKitapDatabaseSeeder;

class HisabKitapErpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HisabKitapDatabaseSeeder::class);
    }

    public function test_public_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('HisabKitap ERP');
        $response->assertSee('Zero Discrepancy', false);
        $response->assertSee('Admin Login');
    }

    public function test_admin_dashboard_page_loads_with_kpis(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('HisabKitap ERP');
        $response->assertSee('700,000');
        $response->assertSee('674,500');
        $response->assertSee('17,500');

        // Verify typo alias /admin/dashoard
        $aliasResponse = $this->get('/admin/dashoard');
        $aliasResponse->assertStatus(200);
    }

    public function test_pso_management_page_loads(): void
    {
        $response = $this->get('/pso');
        $response->assertStatus(200);
        $response->assertSee('PSO Series Management');
        $response->assertSee('PSO 1 - Main Wholesale Counter');
    }

    public function test_bill_verification_page_and_missing_bill_resolution(): void
    {
        $response = $this->get('/verification');
        $response->assertStatus(200);
        $response->assertSee('Bill Sequence Verification');
        $response->assertSee('CB 02');

        // Resolve missing bill CB 02
        $res = $this->postJson('/verification/resolve-missing', [
            'bill_no' => 'CB 02',
            'reason' => 'Physical Slip Found & Verified in Counter Bundle',
            'remark' => 'Counter bundle #1 inspected and verified'
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        // Verify master recon is now balanced
        $reconResponse = $this->get('/reconciliation');
        $reconResponse->assertStatus(200);
        $reconResponse->assertSee('RECONCILIATION 100% BALANCED');
    }

    public function test_digital_sealing_flow(): void
    {
        // First resolve missing bill
        $this->postJson('/verification/resolve-missing', [
            'bill_no' => 'CB 02',
            'reason' => 'Physical Slip Found & Verified in Counter Bundle',
            'remark' => 'Verified'
        ]);

        // Attempt seal
        $response = $this->post('/approval-sealing/seal');
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check if page displays sealed banner
        $appResponse = $this->get('/approval-sealing');
        $appResponse->assertStatus(200);
        $appResponse->assertSee('PSO SEALED & LOCKED', false);
    }

    public function test_login_page_loads_and_shows_personas(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign In to Dashboard', false);
        $response->assertSee('Ramesh Sharma');
        $response->assertSee('Pooja Verma');
        $response->assertSee('Suresh Gupta');
        $response->assertSee('Vikram Mehta');
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

    public function test_quick_persona_login(): void
    {
        $response = $this->get('/admin/login/quick?role_code=usr_02');
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }
}
