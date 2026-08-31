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

    public function test_dashboard_page_loads_with_kpis(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('HisabKitap ERP');
        $response->assertSee('700,000');
        $response->assertSee('674,500');
        $response->assertSee('17,500');
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

    public function test_payment_classification_and_credit_collection(): void
    {
        $payResponse = $this->get('/payment-classification');
        $payResponse->assertStatus(200);
        $payResponse->assertSee('Payment Classification', false);

        $creditResponse = $this->get('/credit-collection');
        $creditResponse->assertStatus(200);
        $creditResponse->assertSee('Credit Collection Management', false);
    }
}
