<?php

namespace Tests\Feature;

use App\Models\PsoConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pso_index_page_loads_and_has_links_to_create_and_edit(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $pso = PsoConfig::create([
            'code' => 'PSO-TEST-1',
            'prefix' => 'TS',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Operator Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pso.index'));
        $response->assertStatus(200);
        $response->assertSee(route('admin.pso.create'));
        $response->assertSee(route('admin.pso.edit', $pso->id));
        $response->assertDontSee('#modal-add-pso');
        $response->assertDontSee('<th>PSO Name</th>');
    }

    public function test_pso_create_page_loads_successfully_with_readonly_code(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.pso.create'));
        $response->assertStatus(200);
        $response->assertSee('Configure New PSO Series');
        $response->assertSee('Save PSO Configuration');
        $response->assertSee('Bill Series & Sequence Range', false);
        $response->assertSee('Add More Range');
        $response->assertSee('name="driver_name"', false);
        $response->assertSee('name="gadi_number"', false);
        $response->assertSee('name="helper_1"', false);
        $response->assertSee('name="helper_2"', false);
        $response->assertSee('name="helper_3"', false);
        $response->assertSee('Driver Name');
        $response->assertSee('Helper 1');
        $response->assertSee('Gadi Number');
        $response->assertSee('readonly');
        $response->assertSee('series[0][prefix]', false);
        $response->assertSee('series[0][financial_year]', false);
        $response->assertSee('series[0][start_no]', false);
        $response->assertSee('series[0][end_no]', false);
        $response->assertDontSee('PSO Display Name / Purpose');
    }

    public function test_pso_store_action_creates_record_with_auto_generated_code(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.pso.store'), [
            'prefix' => 'AD',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Ramesh Sharma',
            'driver_name' => 'Dilip Driver',
            'helper_1' => 'Main Helper',
            'helper_2' => 'Second Helper',
            'gadi_number' => 'RJ 14 GA 5555',
            'specials' => 'ITC 01, SPL 02',
            'description' => 'Airport delivery special series',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.pso.index'));
        $this->assertDatabaseHas('pso_configs', [
            'code' => 'PSO-1',
            'prefix' => 'AD',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Ramesh Sharma',
            'driver_name' => 'Dilip Driver',
            'helper_1' => 'Main Helper',
            'helper_2' => 'Second Helper',
            'gadi_number' => 'RJ 14 GA 5555',
        ]);
    }

    public function test_pso_store_action_creates_record_with_multiple_series_ranges(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.pso.store'), [
            'series' => [
                [
                    'prefix' => 'CB',
                    'financial_year' => '2026-2027',
                    'start_no' => 1,
                    'end_no' => 10,
                ],
                [
                    'prefix' => 'ITC',
                    'financial_year' => '2026-2027',
                    'start_no' => 1,
                    'end_no' => 5,
                ]
            ],
            'operator_name' => 'Multi Range Operator',
            'driver_name' => 'Raju Driver',
            'helper_1' => 'Helper One',
            'description' => 'Multi-range configuration',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.pso.index'));
        
        $pso = PsoConfig::where('operator_name', 'Multi Range Operator')->first();
        $this->assertNotNull($pso);
        $this->assertEquals('CB', $pso->prefix);
        $this->assertEquals('Raju Driver', $pso->driver_name);
        $this->assertEquals('Helper One', $pso->helper_1);
        $this->assertEquals(1, $pso->start_no);
        $this->assertEquals(10, $pso->end_no);
        $this->assertIsArray($pso->series_ranges);
        $this->assertCount(2, $pso->series_ranges);
        $this->assertEquals('ITC', $pso->series_ranges[1]['prefix']);
    }

    public function test_pso_edit_page_loads_with_existing_data(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $pso = PsoConfig::create([
            'code' => 'PSO-EDIT-ME',
            'prefix' => 'PE',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Big Bite',
            'driver_name' => 'Sunil Yadav',
            'helper_1' => 'Amit Kumar',
            'helper_2' => 'Vikas Singh',
            'gadi_number' => 'DL 01 AB 9999',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pso.edit', $pso->id));
        $response->assertStatus(200);
        $response->assertSee('PSO-EDIT-ME');
        $response->assertSee('Sunil Yadav');
        $response->assertSee('Amit Kumar');
        $response->assertSee('Vikas Singh');
        $response->assertSee('DL 01 AB 9999');
        $response->assertSee('Update PSO Configuration');
        $response->assertSee('Bill Series & Sequence Range', false);
        $response->assertSee('Add More Range');
        $response->assertSee('readonly');
        $response->assertSee('series[0][prefix]', false);
        $response->assertSee('series[0][financial_year]', false);
        $response->assertSee('series[0][start_no]', false);
        $response->assertSee('series[0][end_no]', false);
        $response->assertDontSee('PSO Display Name / Purpose');
    }

    public function test_pso_update_action_modifies_record_and_redirects(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $pso = PsoConfig::create([
            'code' => 'PSO-ORIG',
            'prefix' => 'OG',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Big Bite',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pso.update', $pso->id), [
            'prefix' => 'UP',
            'start_no' => 5,
            'end_no' => 15,
            'operator_name' => 'Suresh Gupta',
            'specials' => 'SPEC 1',
            'description' => 'Updated notes',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.pso.index'));
        $this->assertDatabaseHas('pso_configs', [
            'id' => $pso->id,
            'code' => 'PSO-ORIG',
            'prefix' => 'UP',
            'start_no' => 5,
            'end_no' => 15,
        ]);
    }

    public function test_pso_update_action_modifies_record_with_multiple_series_ranges(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $pso = PsoConfig::create([
            'code' => 'PSO-ORIG2',
            'prefix' => 'OG',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Initial Operator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pso.update', $pso->id), [
            'series' => [
                [
                    'prefix' => 'UP1',
                    'financial_year' => '2026-2027',
                    'start_no' => 2,
                    'end_no' => 12,
                ],
                [
                    'prefix' => 'UP2',
                    'financial_year' => '2026-2027',
                    'start_no' => 20,
                    'end_no' => 30,
                ]
            ],
            'operator_name' => 'Updated Operator',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.pso.index'));
        
        $pso->refresh();
        $this->assertEquals('UP1', $pso->prefix);
        $this->assertEquals(2, $pso->start_no);
        $this->assertEquals(12, $pso->end_no);
        $this->assertCount(2, $pso->series_ranges);
        $this->assertEquals('UP2', $pso->series_ranges[1]['prefix']);
        $this->assertEquals(20, $pso->series_ranges[1]['start_no']);
    }

    public function test_pso_stores_and_updates_driver_three_helpers_and_gadi_number(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        // 1. Test Store with Driver, 3 Helpers, and Gadi Number
        $response = $this->actingAs($admin)->post(route('admin.pso.store'), [
            'prefix' => 'CR',
            'start_no' => 1,
            'end_no' => 20,
            'operator_name' => 'Operator Vijay',
            'driver_name' => 'Driver Suraj',
            'helper_1' => 'Helper Ram',
            'helper_2' => 'Helper Shyam',
            'helper_3' => 'Helper Mohan',
            'gadi_number' => 'RJ 14 GA 7788',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.pso.index'));

        $pso = PsoConfig::where('driver_name', 'Driver Suraj')->first();
        $this->assertNotNull($pso);
        $this->assertEquals('Driver Suraj', $pso->driver_name);
        $this->assertEquals('Helper Ram', $pso->helper_1);
        $this->assertEquals('Helper Shyam', $pso->helper_2);
        $this->assertEquals('Helper Mohan', $pso->helper_3);
        $this->assertEquals('RJ 14 GA 7788', $pso->gadi_number);
        $this->assertEquals('RJ 14 GA 7788', $pso->vehicle_no);
        $this->assertCount(3, $pso->helpers_list);
        $this->assertEquals('Helper Ram, Helper Shyam, Helper Mohan', $pso->helpers_text);

        // 2. Test Index View displays the driver and gadi info
        $indexResponse = $this->actingAs($admin)->get(route('admin.pso.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Driver Suraj');
        $indexResponse->assertSee('RJ 14 GA 7788');
        $indexResponse->assertSee('Helper Ram, Helper Shyam, Helper Mohan');

        // 3. Test Update Driver, Helpers, and Gadi
        $updateResponse = $this->actingAs($admin)->post(route('admin.pso.update', $pso->id), [
            'prefix' => 'CR',
            'start_no' => 1,
            'end_no' => 20,
            'operator_name' => 'Operator Vijay',
            'driver_name' => 'Driver Naresh',
            'helper_1' => 'Helper Sonu',
            'helper_2' => 'Helper Monu',
            'helper_3' => '', // optional third helper cleared
            'gadi_number' => 'DL 01 ZZ 9900',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.pso.index'));

        $pso->refresh();
        $this->assertEquals('Driver Naresh', $pso->driver_name);
        $this->assertEquals('Helper Sonu', $pso->helper_1);
        $this->assertEquals('Helper Monu', $pso->helper_2);
        $this->assertNull($pso->helper_3);
        $this->assertEquals('DL 01 ZZ 9900', $pso->gadi_number);
        $this->assertCount(2, $pso->helpers_list);
        $this->assertEquals('Helper Sonu, Helper Monu', $pso->helpers_text);
    }
}
