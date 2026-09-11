<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PsoConfig;
use App\Models\Bill;
use App\Models\CashDenomination;
use App\Services\ReconciliationService;

class CashDenominationAndSplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\HisabKitapDatabaseSeeder::class);
        $this->post('/admin/login', [
            'email' => 'admin@hisabkitap.in',
            'password' => 'password',
        ]);
    }

    public function test_cash_denomination_page_loads_and_displays_proper_metrics(): void
    {
        $response = $this->get('/admin/cash-denomination');
        $response->assertStatus(200);
        $response->assertSee('Cash Denomination');
        $response->assertSee('Physical Cash Denomination Counter');
        $response->assertSee('Taxi / Driver KM Travel Deduction');
    }

    public function test_cash_denomination_stores_notes_km_and_calculates_short_cash(): void
    {
        $pso = PsoConfig::create([
            'code' => 'PSO-1',
            'name' => 'Main Wholesale Counter',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Manoj Gupta',
            'driver_name' => 'Ramesh Kumar',
            'gadi_number' => 'RJ 14 GA 5555',
            'is_active' => true,
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        // Post Cash Denomination entry:
        // 40 x 500 = 20,000
        // 20 x 200 = 4,000
        // 8 x 100  = 800
        // Total Physical = 24,800
        // KM = 200 KM, Rate = 2.0 -> KM Allowance = 400
        // Book Cash = 25,400 -> Expected Deposit = 25,400 - 400 = 25,000
        // Physical = 24,800 -> Short Cash = 200
        $postData = [
            'business_date' => '2026-08-14',
            'pso_code' => 'PSO-1',
            'driver_name' => 'Ramesh Kumar',
            'gadi_number' => 'RJ 14 GA 5555',
            'notes_500' => 40,
            'notes_200' => 20,
            'notes_100' => 8,
            'coins_total' => 0,
            'total_km' => 200,
            'km_rate' => 2.0,
            'book_cash_amount' => 25400,
            'cashier_name' => 'Pooja Verma',
            'remarks' => 'Driver trip completed',
        ];

        $res = $this->post('/admin/cash-denomination/store', $postData);
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $this->assertDatabaseHas('cash_denominations', [
            'pso_code' => 'PSO-1',
            'driver_name' => 'Ramesh Kumar',
            'gadi_number' => 'RJ 14 GA 5555',
            'total_physical_cash' => 24800.00,
            'total_km' => 200.00,
            'km_rate' => 2.00,
            'km_allowance_amount' => 400.00,
            'short_cash_amount' => 200.00,
        ]);
    }

    public function test_split_bill_payment_reconciliation(): void
    {
        $pso = PsoConfig::create([
            'code' => 'PSO-1',
            'name' => 'Main Wholesale Counter',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Manoj Gupta',
            'is_active' => true,
        ]);

        // Create a single bill of 25,000 with split payment: 15,000 Cash + 10,000 Paytm
        $bill = Bill::create([
            'bill_no' => 'CB 01',
            'pso_config_id' => $pso->id,
            'pso_code' => 'PSO-1',
            'business_date' => '2026-08-14',
            'customer_name' => 'Apex Enterprises',
            'amount' => 25000,
            'net_amount' => 25000,
            'cash_amount' => 15000,
            'paytm_amount' => 10000,
            'is_split_payment' => true,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        $service = app(ReconciliationService::class);
        $metrics = $service->getMetrics('2026-08-14');

        $this->assertEquals(25000.00, $metrics['tallyTotal']);
        $this->assertEquals(15000.00, $metrics['totCash']);
        $this->assertEquals(10000.00, $metrics['totPaytm']);
        $this->assertEquals(25000.00, $metrics['psoCollection']);
        $this->assertEquals(0.00, $metrics['difference']);
        $this->assertTrue($metrics['isReconciled']);
    }

    public function test_reconciliation_page_shows_7_metric_summary_matrix(): void
    {
        $response = $this->get('/admin/reconciliation');
        $response->assertStatus(200);
        $response->assertSee('Driver Reconciliation Matrix');
        $response->assertSee('Total Bill Amount (DayBook Gross)');
        $response->assertSee('Pending / Short Cash Variance');
        $response->assertSee('KM / Driver Travel Allowance');
    }

    public function test_store_manual_bill_with_split_payment(): void
    {
        $pso = PsoConfig::create([
            'code' => 'PSO-1',
            'name' => 'Main Wholesale Counter',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Manoj Gupta',
            'is_active' => true,
        ]);

        $response = $this->post('/admin/verification/store-manual', [
            'bill_no' => 'CB 05',
            'pso_code' => 'PSO-1',
            'business_date' => '2026-08-14',
            'customer_name' => 'Kailash Sweet Center',
            'amount' => 12000,
            'payment_type' => 'Split',
            'cash_amount' => 7000,
            'paytm_amount' => 5000,
            'cd_amount' => 0,
            'refund_amount' => 0,
            'remark' => 'Manual counter split bill',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bills', [
            'bill_no' => 'CB 05',
            'customer_name' => 'Kailash Sweet Center',
            'amount' => 12000.00,
            'cash_amount' => 7000.00,
            'paytm_amount' => 5000.00,
            'is_split_payment' => true,
        ]);
    }

    public function test_split_payment_and_denomination_respects_net_amount(): void
    {
        $pso = PsoConfig::create([
            'code' => 'PSO-1',
            'name' => 'Main Wholesale Counter',
            'prefix' => 'SC',
            'start_no' => 6700,
            'end_no' => 6720,
            'operator_name' => 'Operator One',
            'is_active' => false,
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        $bill = Bill::create([
            'bill_no' => 'Sc/26-27/6713',
            'pso_config_id' => $pso->id,
            'pso_code' => 'PSO-1',
            'business_date' => '2026-08-14',
            'customer_name' => 'GANPATI SWEET & NAMKEEN',
            'amount' => 4240.00,
            'payment_type' => 'Cash',
            'cd_amount' => 0.00,
            'refund_amount' => 240.00,
            'net_amount' => 4000.00,
            'cash_amount' => 4000.00,
            'paytm_amount' => 0.00,
            'status' => 'Matched',
        ]);

        // Verify denomination controller calculates book cash based on Net Amount (4000)
        $service = app(ReconciliationService::class);
        $metrics = $service->getMetrics('2026-08-14');
        $this->assertEquals(4000.00, $metrics['totCash']);

        $res = $this->get('/admin/cash-denomination?date=2026-08-14&pso=PSO-1');
        $res->assertStatus(200);
        $res->assertViewHas('scopedBookCash', 4000.00);

        // Update to split payment via inline update route
        $updateRes = $this->postJson(route('admin.verification.update'), [
            'bill_id' => $bill->id,
            'payment_type' => 'Cash',
            'is_split_payment' => 1,
            'cash_amount' => 2500.00,
            'paytm_amount' => 1500.00,
            'cd_amount' => 0.00,
            'refund_amount' => 240.00,
        ]);

        $updateRes->assertStatus(200);
        $updateRes->assertJson([
            'success' => true,
            'bill' => [
                'net_amount' => '4000.00',
            ]
        ]);

        $bill->refresh();
        $this->assertEquals(4000.00, (float)$bill->net_amount);
        $this->assertEquals(2500.00, (float)$bill->cash_amount);
        $this->assertEquals(1500.00, (float)$bill->paytm_amount);
    }

    public function test_cash_denomination_only_shows_closed_psos(): void
    {
        PsoConfig::create([
            'code' => 'PSO-OPEN-UNCLOSED',
            'prefix' => 'OP',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Open Operator',
            'is_active' => true,
            'is_closed' => false,
        ]);

        PsoConfig::create([
            'code' => 'PSO-CLOSED-FINAL',
            'prefix' => 'CL',
            'start_no' => 11,
            'end_no' => 20,
            'operator_name' => 'Closed Operator',
            'is_active' => false,
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        $response = $this->get('/admin/cash-denomination');
        $response->assertStatus(200);
        $response->assertSee('PSO-CLOSED-FINAL');
        $response->assertDontSee('PSO-OPEN-UNCLOSED');

        $verificationResponse = $this->get('/admin/verification');
        $verificationResponse->assertStatus(200);
        $verificationResponse->assertSee('PSO-CLOSED-FINAL');
        $verificationResponse->assertDontSee('PSO-OPEN-UNCLOSED');
    }
}
