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
        $response->assertSee('readonly');
        $response->assertSee('name="prefix"', false);
        $response->assertSee('name="financial_year"', false);
        $response->assertSee('name="start_no"', false);
        $response->assertSee('name="end_no"', false);
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
        ]);
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
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pso.edit', $pso->id));
        $response->assertStatus(200);
        $response->assertSee('PSO-EDIT-ME');
        $response->assertSee('Update PSO Configuration');
        $response->assertSee('readonly');
        $response->assertSee('name="prefix"', false);
        $response->assertSee('name="financial_year"', false);
        $response->assertSee('name="start_no"', false);
        $response->assertSee('name="end_no"', false);
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
}
