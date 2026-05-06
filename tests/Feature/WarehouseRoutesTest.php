<?php

namespace Tests\Feature;

use App\Bundles\Warehouse\Livewire\Slabs;
use App\Bundles\Warehouse\Livewire\Dashboard;
use App\Bundles\Warehouse\Livewire\Materials;
use App\Bundles\Warehouse\Livewire\ItemFlow;
use App\Bundles\Warehouse\Livewire\Inventory;
use App\Bundles\Warehouse\Livewire\Profile;
use App\Bundles\Warehouse\Models\InventoryCheck;
use App\Bundles\Warehouse\Models\Material;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\SlabStatus;
use App\Bundles\Warehouse\Utils\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(UserRole::cases())
            ->each(fn (UserRole $role) => Role::findOrCreate($role->value));
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/warehouse')->assertRedirect('/login');
    }

    public function test_user_can_open_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/warehouse')
            ->assertOk();
    }

    public function test_user_can_switch_to_slovak_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/language/sk')
            ->assertRedirect();

        $this->assertSame('sk', session('locale'));

        $this->actingAs($user)
            ->get('/warehouse')
            ->assertOk()
            ->assertSee('Prehľad')
            ->assertSee('Položky');
    }

    public function test_worker_cannot_see_warehouse_activity_on_dashboard(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        StockMovement::create([
            'type' => 'item',
            'action' => 'inventory',
            'subject_name' => 'PRIVATE-DASHBOARD-ACTIVITY',
            'actor_id' => $worker->id,
            'description' => 'Private dashboard activity text.',
        ]);

        Livewire::actingAs($worker)
            ->test(Dashboard::class)
            ->assertDontSee('Warehouse activity')
            ->assertDontSee('PRIVATE-DASHBOARD-ACTIVITY')
            ->assertDontSee('Private dashboard activity text.');
    }

    public function test_manager_can_see_warehouse_activity_on_dashboard(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        StockMovement::create([
            'type' => 'item',
            'action' => 'inventory',
            'subject_name' => 'VISIBLE-DASHBOARD-ACTIVITY',
            'actor_id' => $manager->id,
            'description' => 'Visible dashboard activity text.',
        ]);

        Livewire::actingAs($manager)
            ->test(Dashboard::class)
            ->assertSee('Warehouse activity')
            ->assertSee('VISIBLE-DASHBOARD-ACTIVITY')
            ->assertSee('Visible dashboard activity text.');
    }

    public function test_admin_can_open_employees_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        $this->actingAs($admin)
            ->get('/warehouse/employees')
            ->assertOk();
    }

    public function test_manager_cannot_open_employees_page(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $this->actingAs($manager)
            ->get('/warehouse/employees')
            ->assertForbidden();
    }

    public function test_worker_can_open_items_but_not_materials(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $this->actingAs($worker)
            ->get('/warehouse/slabs')
            ->assertOk();

        $this->actingAs($worker)
            ->get('/warehouse/materials')
            ->assertForbidden();
    }

    public function test_worker_can_open_inventory_page(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $this->actingAs($worker)
            ->get('/warehouse/inventory')
            ->assertOk();
    }

    public function test_worker_can_use_active_inventory_without_seeing_previous_checks(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        InventoryCheck::create([
            'name' => 'Old private inventory',
            'status' => 'completed',
            'started_by_id' => $manager->id,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        InventoryCheck::create([
            'name' => 'Active worker inventory',
            'status' => 'active',
            'started_by_id' => $manager->id,
            'started_at' => now(),
        ]);

        Livewire::actingAs($worker)
            ->test(Inventory::class)
            ->assertSee('Active worker inventory')
            ->assertSee('Scan barcode')
            ->assertDontSee('Recent checks')
            ->assertDontSee('Old private inventory');
    }

    public function test_worker_can_start_inventory_without_seeing_previous_checks(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $worker->id,
            'code' => 'WORKER-INV-001',
            'barcode' => 'WORKER-INV-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($worker)
            ->test(Inventory::class)
            ->set('name', 'Worker inventory')
            ->call('startInventory')
            ->assertHasNoErrors()
            ->assertSee('Worker inventory')
            ->assertDontSee('Recent checks');

        $check = InventoryCheck::where('name', 'Worker inventory')->firstOrFail();

        $this->assertSame($worker->id, $check->started_by_id);
        $this->assertDatabaseHas('inventory_check_items', [
            'inventory_check_id' => $check->id,
            'slab_id' => $slab->id,
        ]);
    }

    public function test_worker_can_complete_inventory(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $worker->id,
            'code' => 'WORKER-COMPLETE-001',
            'barcode' => 'WORKER-COMPLETE-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($worker)
            ->test(Inventory::class)
            ->set('name', 'Worker complete inventory')
            ->call('startInventory')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $check = InventoryCheck::where('name', 'Worker complete inventory')->firstOrFail();

        $this->assertSame('completed', $check->status);
        $this->assertSame($worker->id, $check->completed_by_id);

        $this->assertDatabaseHas('inventory_check_items', [
            'inventory_check_id' => $check->id,
            'slab_id' => $slab->id,
            'result' => 'missing',
        ]);
    }

    public function test_items_filters_can_be_cleared(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        Livewire::actingAs($worker)
            ->test(Slabs::class)
            ->set('search', 'CAR')
            ->set('statusFilter', SlabStatus::Available->value)
            ->set('materialFilter', '12')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('materialFilter', '');
    }

    public function test_user_can_open_item_detail_page(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $worker->id,
            'code' => 'DETAIL-001',
            'barcode' => 'DETAIL-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        $this->actingAs($worker)
            ->get("/warehouse/slabs/{$slab->id}")
            ->assertOk()
            ->assertSee('DETAIL-001');
    }

    public function test_worker_cannot_see_item_history_on_detail_page(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $worker->id,
            'code' => 'PRIVATE-HISTORY',
            'barcode' => 'PRIVATE-HISTORY-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        StockMovement::create([
            'type' => 'item',
            'action' => 'updated',
            'subject_id' => $slab->id,
            'subject_name' => $slab->code,
            'actor_id' => $worker->id,
            'description' => 'Private movement text.',
        ]);

        $this->actingAs($worker)
            ->get("/warehouse/slabs/{$slab->id}")
            ->assertOk()
            ->assertDontSee('Movement history')
            ->assertDontSee('Inventory history')
            ->assertDontSee('Private movement text.');
    }

    public function test_manager_can_see_item_history_on_detail_page(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'VISIBLE-HISTORY',
            'barcode' => 'VISIBLE-HISTORY-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        StockMovement::create([
            'type' => 'item',
            'action' => 'updated',
            'subject_id' => $slab->id,
            'subject_name' => $slab->code,
            'actor_id' => $manager->id,
            'description' => 'Visible movement text.',
        ]);

        $this->actingAs($manager)
            ->get("/warehouse/slabs/{$slab->id}")
            ->assertOk()
            ->assertSee('Movement history')
            ->assertSee('Inventory history')
            ->assertSee('Visible movement text.');
    }

    public function test_user_can_open_own_profile(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $this->actingAs($worker)
            ->get('/warehouse/profile')
            ->assertOk();
    }

    public function test_admin_can_open_employee_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $this->actingAs($admin)
            ->get("/warehouse/employees/{$worker->id}/profile")
            ->assertOk();
    }

    public function test_worker_cannot_open_employee_profile_route(): void
    {
        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $otherWorker = User::factory()->create();
        $otherWorker->assignRole(UserRole::Worker->value);

        $this->actingAs($worker)
            ->get("/warehouse/employees/{$otherWorker->id}/profile")
            ->assertForbidden();
    }

    public function test_admin_can_edit_own_profile_from_profile_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        Livewire::actingAs($admin)
            ->test(Profile::class)
            ->call('editProfile')
            ->set('name', 'Updated Admin')
            ->set('email', 'updated-admin@example.com')
            ->set('phone', '+421 900 123 456')
            ->set('position', 'Warehouse Owner')
            ->set('department', 'Operations')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.com',
            'phone' => '+421 900 123 456',
            'position' => 'Warehouse Owner',
            'department' => 'Operations',
        ]);
    }

    public function test_status_change_is_logged_without_enum_cast_error(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'CAR-TEST',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Sold,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Slabs::class)
            ->call('edit', $slab)
            ->set('status', SlabStatus::Damaged->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Damaged->value,
        ]);

        $movement = StockMovement::query()
            ->where('subject_name', 'CAR-TEST')
            ->latest()
            ->first();

        $this->assertSame('updated', $movement?->action);
        $this->assertSame('Sold', $movement->changes['status']['from']);
        $this->assertSame('Damaged', $movement->changes['status']['to']);
    }

    public function test_material_delete_moves_items_to_replacement_material(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $oldMaterial = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $replacementMaterial = Material::create([
            'name' => 'Emperador',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $oldMaterial->id,
            'created_by_id' => $manager->id,
            'code' => 'MOVE-TEST',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Materials::class)
            ->call('delete', $oldMaterial)
            ->assertSet('showReplacementModal', true)
            ->set('replacementMaterialId', $replacementMaterial->id)
            ->call('replaceAndDelete')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('materials', ['id' => $oldMaterial->id]);
        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'material_id' => $replacementMaterial->id,
        ]);
    }

    public function test_manager_archives_item_instead_of_hard_deleting_it(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'ARCH-001',
            'barcode' => 'ARCH-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Slabs::class)
            ->call('delete', $slab)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('slabs', ['id' => $slab->id]);
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'item',
            'action' => 'archived',
            'subject_name' => 'ARCH-001',
        ]);
    }

    public function test_manager_can_receive_item_with_generated_barcode(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Travertine',
            'is_active' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(ItemFlow::class)
            ->set('material_id', $material->id)
            ->set('code', 'ARR-001')
            ->set('length_cm', 300)
            ->set('width_cm', 160)
            ->set('thickness_cm', 2)
            ->set('source', 'Italy quarry')
            ->set('supplier', 'Stone Supplier')
            ->set('location', 'Rack D1')
            ->call('receive')
            ->assertHasNoErrors();

        $slab = Slab::where('code', 'ARR-001')->firstOrFail();

        $this->assertSame('WH-'.str_pad((string) $slab->id, 6, '0', STR_PAD_LEFT), $slab->barcode);
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'item',
            'action' => 'arrived',
            'subject_name' => 'ARR-001',
        ]);
    }

    public function test_manager_can_ship_item_from_item_flow(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Travertine',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'SHIP-001',
            'barcode' => 'SHIP-001',
            'length_cm' => 300,
            'width_cm' => 160,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack D1',
        ]);

        Livewire::actingAs($manager)
            ->test(ItemFlow::class)
            ->call('showShip')
            ->set('slab_id', $slab->id)
            ->call('ship')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Sold->value,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'item',
            'action' => 'shipped',
            'subject_name' => 'SHIP-001',
        ]);
    }

    public function test_inventory_scan_marks_item_as_found(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $worker = User::factory()->create();
        $worker->assignRole(UserRole::Worker->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'INV-001',
            'barcode' => 'INV-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'May inventory')
            ->call('startInventory')
            ->assertHasNoErrors();

        $check = InventoryCheck::where('name', 'May inventory')->firstOrFail();

        Livewire::actingAs($worker)
            ->test(Inventory::class)
            ->call('openCheck', $check->id)
            ->set('scanBarcode', 'INV-001-BC')
            ->set('actualLocation', 'Rack A1')
            ->set('actualStatus', SlabStatus::Available->value)
            ->call('scan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_check_items', [
            'inventory_check_id' => $check->id,
            'slab_id' => $slab->id,
            'result' => 'found',
            'checked_by_id' => $worker->id,
        ]);
    }

    public function test_completing_inventory_marks_unchecked_items_missing(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'MISS-001',
            'barcode' => 'MISS-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'Missing inventory')
            ->call('startInventory')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $check = InventoryCheck::where('name', 'Missing inventory')->firstOrFail();

        $this->assertDatabaseHas('inventory_check_items', [
            'inventory_check_id' => $check->id,
            'slab_id' => $slab->id,
            'result' => 'missing',
        ]);

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Missing->value,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'item',
            'action' => 'inventory',
            'subject_name' => 'MISS-001',
        ]);

        $this->assertDatabaseHas('inventory_checks', [
            'id' => $check->id,
            'status' => 'completed',
        ]);
    }

    public function test_completing_inventory_applies_checked_item_status_and_location(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'APPLY-001',
            'barcode' => 'APPLY-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Available,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'Apply inventory')
            ->call('startInventory')
            ->set('scanBarcode', 'APPLY-001-BC')
            ->set('actualLocation', 'Rack B9')
            ->set('actualStatus', SlabStatus::Damaged->value)
            ->call('scan')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Damaged->value,
            'location' => 'Rack B9',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'item',
            'action' => 'inventory',
            'subject_name' => 'APPLY-001',
        ]);
    }

    public function test_inventory_does_not_change_found_item_status_when_status_is_not_reported(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'KEEP-001',
            'barcode' => 'KEEP-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Reserved,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'Keep status inventory')
            ->call('startInventory')
            ->set('scanBarcode', 'KEEP-001-BC')
            ->set('actualLocation', 'Rack A1')
            ->call('scan')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Reserved->value,
            'location' => 'Rack A1',
        ]);
    }

    public function test_inventory_ignores_sold_items(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $soldSlab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'SOLD-INV-001',
            'barcode' => 'SOLD-INV-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Sold,
            'location' => 'Customer',
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'Sold ignored inventory')
            ->call('startInventory')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('inventory_check_items', [
            'slab_id' => $soldSlab->id,
        ]);

        $this->assertDatabaseHas('slabs', [
            'id' => $soldSlab->id,
            'status' => SlabStatus::Sold->value,
        ]);
    }

    public function test_missing_item_returns_to_previous_status_when_found_in_next_inventory(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(UserRole::Manager->value);

        $material = Material::create([
            'name' => 'Carrara',
            'is_active' => true,
        ]);

        $slab = Slab::create([
            'material_id' => $material->id,
            'created_by_id' => $manager->id,
            'code' => 'RETURN-001',
            'barcode' => 'RETURN-001-BC',
            'length_cm' => 300,
            'width_cm' => 150,
            'thickness_cm' => 2,
            'status' => SlabStatus::Reserved,
            'location' => 'Rack A1',
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'First inventory')
            ->call('startInventory')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Missing->value,
        ]);

        Livewire::actingAs($manager)
            ->test(Inventory::class)
            ->set('name', 'Second inventory')
            ->call('startInventory')
            ->set('scanBarcode', 'RETURN-001-BC')
            ->set('actualLocation', 'Rack A1')
            ->call('scan')
            ->call('completeInventory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slabs', [
            'id' => $slab->id,
            'status' => SlabStatus::Reserved->value,
            'location' => 'Rack A1',
        ]);
    }
}
