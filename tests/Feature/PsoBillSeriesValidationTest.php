<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\PsoConfig;
use App\Models\User;
use App\Services\ReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsoBillSeriesValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_bill_within_series_is_marked_matched(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso = PsoConfig::create([
            'code' => 'PSO-VALID-1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.verification.store_manual'), [
            'pso_code' => $pso->code,
            'bill_no' => 'CB 05',
            'business_date' => $date,
            'customer_name' => 'Valid Customer',
            'amount' => 1500,
            'payment_type' => 'Cash',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $bill = Bill::where('bill_no', 'CB 05')->where('pso_code', $pso->code)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Matched', $bill->status);
        $this->assertNull($bill->mismatch_status);
        $this->assertFalse($bill->is_mismatch_approved);
        $this->assertTrue($bill->isValidForReconciliation());
    }

    public function test_bill_outside_series_is_marked_bill_series_mismatch(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso = PsoConfig::create([
            'code' => 'PSO-SERIES-TEST',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator Series',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.verification.store_manual'), [
            'pso_code' => $pso->code,
            'bill_no' => 'CB 99',
            'business_date' => $date,
            'customer_name' => 'Mismatch Customer',
            'amount' => 2000,
            'payment_type' => 'Cash',
        ]);

        $response->assertSessionHasNoErrors();

        $bill = Bill::where('bill_no', 'CB 99')->where('pso_code', $pso->code)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('Bill Series Mismatch', $bill->status);
        $this->assertNotNull($bill->mismatch_status);
        $this->assertStringContainsStringIgnoringCase('outside assigned', $bill->mismatch_status);
        $this->assertFalse($bill->is_mismatch_approved);
        $this->assertFalse($bill->isValidForReconciliation());
    }

    public function test_duplicate_bill_across_psos_is_marked_duplicate_pso_mismatch(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso1 = PsoConfig::create([
            'code' => 'PSO-DUP-1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 20,
            'operator_name' => 'Operator 1',
            'is_active' => true,
        ]);

        $pso2 = PsoConfig::create([
            'code' => 'PSO-DUP-2',
            'prefix' => 'CB',
            'start_no' => 21,
            'end_no' => 40,
            'operator_name' => 'Operator 2',
            'is_active' => true,
        ]);

        // Create first bill under PSO 1
        Bill::create([
            'business_date' => $date,
            'pso_code' => $pso1->code,
            'bill_no' => 'CB 05',
            'customer_name' => 'First Customer',
            'amount' => 1000,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        // Now enter the same bill number under PSO 2
        $response = $this->actingAs($admin)->post(route('admin.verification.store_manual'), [
            'pso_code' => $pso2->code,
            'bill_no' => 'CB 05',
            'business_date' => $date,
            'customer_name' => 'Duplicate Customer',
            'amount' => 1200,
            'payment_type' => 'Cash',
        ]);

        $response->assertSessionHasNoErrors();

        $secondBill = Bill::where('bill_no', 'CB 05')->where('pso_code', $pso2->code)->first();
        $this->assertNotNull($secondBill);
        $this->assertEquals('Duplicate / PSO Mismatch', $secondBill->status);
        $this->assertNotNull($secondBill->mismatch_status);
        $this->assertStringContainsString('PSO-DUP-1', $secondBill->mismatch_status);
        $this->assertFalse($secondBill->is_mismatch_approved);
    }

    public function test_unapproved_mismatch_bill_is_excluded_from_reconciliation_and_blocks_sealing(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso = PsoConfig::create([
            'code' => 'PSO-RECON-1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator Recon',
            'is_active' => true,
        ]);

        // 1 Valid bill
        Bill::create([
            'business_date' => $date,
            'pso_code' => $pso->code,
            'bill_no' => 'CB 01',
            'customer_name' => 'Valid Cust',
            'amount' => 1000,
            'payment_type' => 'Cash',
            'status' => 'Matched',
        ]);

        // 1 Mismatch bill
        Bill::create([
            'business_date' => $date,
            'pso_code' => $pso->code,
            'bill_no' => 'CB 55',
            'customer_name' => 'Mismatch Cust',
            'amount' => 500,
            'payment_type' => 'Cash',
            'status' => 'Bill Series Mismatch',
            'mismatch_status' => 'Outside assigned series: CB 01 - CB 10',
            'is_mismatch_approved' => false,
        ]);

        $reconService = app(ReconciliationService::class);
        $metrics = $reconService->getMetrics($date);

        $this->assertEquals(2, $metrics['totalBillsCount']);
        $this->assertEquals(1, $metrics['unapprovedMismatchCount']);
        $this->assertEquals(1500, $metrics['tallyTotal']);
        // PSO Collection should ONLY include the valid 1000 bill
        $this->assertEquals(1000, $metrics['psoCollection']);
        $this->assertEquals(500, $metrics['difference']);
        $this->assertFalse($metrics['isReconciled']);

        // Attempting to seal should fail
        $sealResponse = $this->actingAs($admin)->post(route('admin.approval.seal'));
        $sealResponse->assertSessionHas('error');

        $metricsAfter = $reconService->getMetrics($date);
        $this->assertFalse($metricsAfter['isSealed']);
    }

    public function test_authorized_user_can_approve_mismatch_with_mandatory_reason(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso = PsoConfig::create([
            'code' => 'PSO-APPROVE-1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator A',
            'is_active' => true,
        ]);

        $bill = Bill::create([
            'business_date' => $date,
            'pso_code' => $pso->code,
            'bill_no' => 'CB 88',
            'customer_name' => 'Emergency Customer',
            'amount' => 2500,
            'payment_type' => 'Cash',
            'status' => 'Bill Series Mismatch',
            'mismatch_status' => 'Outside assigned series',
            'is_mismatch_approved' => false,
        ]);

        // Attempting to approve without reason should fail validation
        $failResponse = $this->actingAs($admin)->post(route('admin.verification.approve_mismatch'), [
            'bill_id' => $bill->id,
            'reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('reason');

        // Approve with valid reason
        $successResponse = $this->actingAs($admin)->post(route('admin.verification.approve_mismatch'), [
            'bill_id' => $bill->id,
            'reason' => 'Authorized emergency bill series override by branch manager',
        ]);
        $successResponse->assertSessionHasNoErrors();
        $successResponse->assertRedirect();

        $bill->refresh();
        $this->assertTrue($bill->is_mismatch_approved);
        $this->assertEquals($admin->name, $bill->mismatch_approved_by);
        $this->assertEquals('Authorized emergency bill series override by branch manager', $bill->mismatch_approval_reason);
        $this->assertNotNull($bill->mismatch_approved_at);
        $this->assertTrue($bill->isValidForReconciliation());

        // Reconciliation metrics should now include the approved bill
        $reconService = app(ReconciliationService::class);
        $metrics = $reconService->getMetrics($date);
        $this->assertEquals(0, $metrics['unapprovedMismatchCount']);
        $this->assertEquals(1, $metrics['approvedMismatchCount']);
        $this->assertEquals(2500, $metrics['psoCollection']);
        $this->assertEquals(0, $metrics['difference']);
        $this->assertTrue($metrics['isReconciled']);
    }

    public function test_authorized_user_can_reject_mismatch_with_reason(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso = PsoConfig::create([
            'code' => 'PSO-REJECT-1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator A',
            'is_active' => true,
        ]);

        $bill = Bill::create([
            'business_date' => $date,
            'pso_code' => $pso->code,
            'bill_no' => 'CB 999',
            'customer_name' => 'Rejected Customer',
            'amount' => 1800,
            'payment_type' => 'Cash',
            'status' => 'Bill Series Mismatch',
            'mismatch_status' => 'Outside assigned series',
            'is_mismatch_approved' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.verification.reject_mismatch'), [
            'bill_id' => $bill->id,
            'reason' => 'Invalid bill booklet from previous accounting year, rejected.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $bill->refresh();
        $this->assertFalse($bill->is_mismatch_approved);
        $this->assertEquals($admin->name, $bill->mismatch_rejected_by);
        $this->assertEquals('Invalid bill booklet from previous accounting year, rejected.', $bill->mismatch_rejection_reason);
        $this->assertNotNull($bill->mismatch_rejected_at);
        $this->assertFalse($bill->isValidForReconciliation());
    }

    public function test_revalidate_all_series_syncs_status_after_pso_update(): void
    {
        $admin = User::where('code', 'usr_admin')->first();
        $date = '2026-09-15';

        $pso = PsoConfig::create([
            'code' => 'PSO-SYNC-1',
            'prefix' => 'CB',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator Sync',
            'is_active' => true,
        ]);

        // Bill created with CB 15 (which is outside 1-10)
        $bill = Bill::create([
            'business_date' => $date,
            'pso_code' => $pso->code,
            'bill_no' => 'CB 15',
            'customer_name' => 'Late Sync Customer',
            'amount' => 3000,
            'payment_type' => 'Cash',
            'status' => 'Bill Series Mismatch',
            'mismatch_status' => 'Outside assigned series: CB 01 - CB 10',
            'is_mismatch_approved' => false,
        ]);

        // Update PSO config to expand range to 1-20
        $pso->update(['end_no' => 20]);

        // Trigger revalidation
        $response = $this->actingAs($admin)->post(route('admin.verification.revalidate_series'), [
            'date' => $date,
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $bill->refresh();
        $this->assertEquals('Matched', $bill->status);
        $this->assertNull($bill->mismatch_status);
        $this->assertTrue($bill->isValidForReconciliation());
    }
}
