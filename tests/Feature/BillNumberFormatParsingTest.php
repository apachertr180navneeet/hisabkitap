<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Prefix;
use App\Models\PsoConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillNumberFormatParsingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test parsing all user specified bill formats and legacy formats
     */
    public function test_parse_all_specified_bill_formats(): void
    {
        // 1. Sc/26-27/1
        $sc = PsoConfig::parseBillNumber('Sc/26-27/1');
        $this->assertEquals('Sc', $sc['prefix']);
        $this->assertEquals('26-27', $sc['fy']);
        $this->assertEquals(1, $sc['number']);

        // 2. RB/26-27/1
        $rb = PsoConfig::parseBillNumber('RB/26-27/1');
        $this->assertEquals('RB', $rb['prefix']);
        $this->assertEquals('26-27', $rb['fy']);
        $this->assertEquals(1, $rb['number']);

        // 3. HS/1/26-27
        $hs = PsoConfig::parseBillNumber('HS/1/26-27');
        $this->assertEquals('HS', $hs['prefix']);
        $this->assertEquals('26-27', $hs['fy']);
        $this->assertEquals(1, $hs['number']);

        // 4. 26-27/PG/1
        $pg = PsoConfig::parseBillNumber('26-27/PG/1');
        $this->assertEquals('PG', $pg['prefix']);
        $this->assertEquals('26-27', $pg['fy']);
        $this->assertEquals(1, $pg['number']);

        // 5. I/26-27/000001 (Zero padded serial number)
        $iPadded = PsoConfig::parseBillNumber('I/26-27/000001');
        $this->assertEquals('I', $iPadded['prefix']);
        $this->assertEquals('26-27', $iPadded['fy']);
        $this->assertEquals(1, $iPadded['number']);

        // 6. 26-27/AT/1
        $at = PsoConfig::parseBillNumber('26-27/AT/1');
        $this->assertEquals('AT', $at['prefix']);
        $this->assertEquals('26-27', $at['fy']);
        $this->assertEquals(1, $at['number']);

        // 7. Legacy formats: CB 01, CB-15, SC001, CB15, SC/6376
        $cb1 = PsoConfig::parseBillNumber('CB 01');
        $this->assertEquals('CB', $cb1['prefix']);
        $this->assertEquals(1, $cb1['number']);

        $cb15 = PsoConfig::parseBillNumber('CB-15');
        $this->assertEquals('CB', $cb15['prefix']);
        $this->assertEquals(15, $cb15['number']);

        $sc6376 = PsoConfig::parseBillNumber('SC/6376');
        $this->assertEquals('SC', $sc6376['prefix']);
        $this->assertEquals(6376, $sc6376['number']);

        $compact = PsoConfig::parseBillNumber('CB15');
        $this->assertEquals('CB', $compact['prefix']);
        $this->assertEquals(15, $compact['number']);
    }

    /**
     * Test formatting helper methods
     */
    public function test_format_bill_number_helpers(): void
    {
        // Standard {PREFIX}/{FY}/{NO}
        $formatted1 = PsoConfig::formatBillNumber('Sc', 1, '26-27', '{PREFIX}/{FY}/{NO}');
        $this->assertEquals('Sc/26-27/1', $formatted1);

        // {PREFIX}/{NO}/{FY}
        $formatted2 = PsoConfig::formatBillNumber('HS', 1, '26-27', '{PREFIX}/{NO}/{FY}');
        $this->assertEquals('HS/1/26-27', $formatted2);

        // {FY}/{PREFIX}/{NO}
        $formatted3 = PsoConfig::formatBillNumber('PG', 1, '26-27', '{FY}/{PREFIX}/{NO}');
        $this->assertEquals('26-27/PG/1', $formatted3);

        // {PREFIX}/{FY}/{000000} (padded)
        $formatted4 = PsoConfig::formatBillNumber('I', 1, '26-27', '{PREFIX}/{FY}/{000000}');
        $this->assertEquals('I/26-27/000001', $formatted4);
    }

    /**
     * Test PSO validation with different bill formats
     */
    public function test_pso_series_validation_with_multi_formats(): void
    {
        $pso = PsoConfig::create([
            'code' => 'PSO-FORMAT-TEST',
            'prefix' => 'PG',
            'start_no' => 1,
            'end_no' => 50,
            'operator_name' => 'Operator Format',
            'is_active' => true,
        ]);

        // Valid FY/PREFIX/NO format: 26-27/PG/1
        $res1 = $pso->validateBillNumber('26-27/PG/1');
        $this->assertTrue($res1['valid']);

        // Valid inside range: 26-27/PG/25
        $res2 = $pso->validateBillNumber('26-27/PG/25');
        $this->assertTrue($res2['valid']);

        // Outside range: 26-27/PG/99
        $res3 = $pso->validateBillNumber('26-27/PG/99');
        $this->assertFalse($res3['valid']);
        $this->assertEquals('Bill Series Mismatch', $res3['mismatch_type']);

        // Mismatched prefix: 26-27/AT/1 against PG
        $res4 = $pso->validateBillNumber('26-27/AT/1');
        $this->assertFalse($res4['valid']);
    }

    /**
     * Test Prefix Master CRUD with format configuration
     */
    public function test_prefix_master_crud_with_bill_format(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        // Create new prefix with custom format
        $response = $this->actingAs($admin)->post(route('admin.prefix.store'), [
            'prefix' => 'HS',
            'name' => 'Hindustan Series',
            'bill_format' => '{PREFIX}/{NO}/{FY}',
            'description' => 'Tested via automated test',
        ]);

        $response->assertRedirect();
        $prefix = Prefix::where('prefix', 'HS')->first();
        $this->assertNotNull($prefix);
        $this->assertEquals('{PREFIX}/{NO}/{FY}', $prefix->bill_format);
        $this->assertEquals('HS/1/26-27', $prefix->sample_bill_no);

        // Update prefix format
        $response2 = $this->actingAs($admin)->post(route('admin.prefix.update', $prefix->id), [
            'prefix' => 'HS',
            'name' => 'Hindustan Series Updated',
            'bill_format' => '{FY}/{PREFIX}/{NO}',
        ]);

        $response2->assertRedirect();
        $prefix->refresh();
        $this->assertEquals('{FY}/{PREFIX}/{NO}', $prefix->bill_format);
        $this->assertEquals('26-27/HS/1', $prefix->sample_bill_no);
    }
}
