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
            'name' => 'Test PSO 1',
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
    }

    public function test_pso_create_page_loads_successfully(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.pso.create'));
        $response->assertStatus(200);
        $response->assertSee('Configure New PSO Series');
        $response->assertSee('Save PSO Configuration');
    }

    public function test_pso_store_action_creates_record_and_redirects(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.pso.store'), [
            'code' => 'PSO-99',
            'name' => 'New Airport Delivery Counter',
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
            'code' => 'PSO-99',
            'name' => 'New Airport Delivery Counter',
            'prefix' => 'AD',
        ]);
    }

    public function test_pso_edit_page_loads_with_existing_data(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $pso = PsoConfig::create([
            'code' => 'PSO-EDIT-ME',
            'name' => 'Pre-edit Name',
            'prefix' => 'PE',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Big Bite',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.pso.edit', $pso->id));
        $response->assertStatus(200);
        $response->assertSee('PSO-EDIT-ME');
        $response->assertSee('Pre-edit Name');
        $response->assertSee('Update PSO Configuration');
    }

    public function test_pso_update_action_modifies_record_and_redirects(): void
    {
        $admin = User::where('code', 'usr_admin')->first();

        $pso = PsoConfig::create([
            'code' => 'PSO-ORIG',
            'name' => 'Original Name',
            'prefix' => 'OG',
            'start_no' => 1,
            'end_no' => 10,
            'operator_name' => 'Big Bite',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.pso.update', $pso->id), [
            'code' => 'PSO-UPDATED',
            'name' => 'Updated Name Here',
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
            'code' => 'PSO-UPDATED',
            'name' => 'Updated Name Here',
            'prefix' => 'UP',
            'start_no' => 5,
            'end_no' => 15,
        ]);
    }
}
