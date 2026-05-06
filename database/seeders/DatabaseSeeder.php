<?php

namespace Database\Seeders;

use App\Bundles\Warehouse\Models\InventoryCheck;
use App\Bundles\Warehouse\Models\InventoryCheckItem;
use App\Bundles\Warehouse\Models\Material;
use App\Bundles\Warehouse\Models\Slab;
use App\Bundles\Warehouse\Models\StockMovement;
use App\Bundles\Warehouse\Utils\SlabStatus;
use App\Bundles\Warehouse\Utils\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect(UserRole::cases())
            ->each(fn (UserRole $role) => Role::findOrCreate($role->value));

        $admin = $this->user('Warehouse Admin', 'admin@example.com', UserRole::Admin, [
            'position' => 'Owner',
            'department' => 'Management',
        ]);

        $manager = $this->user('Warehouse Manager', 'manager@example.com', UserRole::Manager, [
            'position' => 'Warehouse manager',
            'department' => 'Operations',
        ]);

        $worker = $this->user('Warehouse Worker', 'worker@example.com', UserRole::Worker, [
            'position' => 'Stock worker',
            'department' => 'Warehouse',
        ]);

        $materials = collect([
            ['name' => 'Carrara', 'description' => 'White marble surface.'],
            ['name' => 'Emperador', 'description' => 'Dark marble surface.'],
            ['name' => 'Travertine', 'description' => 'Warm limestone surface.'],
        ])->map(fn (array $material) => Material::firstOrCreate(
            ['name' => $material['name']],
            [
                'created_by_id' => $admin->id,
                'description' => $material['description'],
                'is_active' => true,
            ]
        ));

        $slabs = collect([
            ['code' => 'CAR-001', 'material' => 'Carrara', 'status' => SlabStatus::Available, 'location' => 'Rack A1'],
            ['code' => 'CAR-002', 'material' => 'Carrara', 'status' => SlabStatus::Reserved, 'location' => 'Rack A2'],
            ['code' => 'CAR-003', 'material' => 'Carrara', 'status' => SlabStatus::Sold, 'location' => 'Customer'],
            ['code' => 'EMP-001', 'material' => 'Emperador', 'status' => SlabStatus::Available, 'location' => 'Rack B1'],
            ['code' => 'EMP-002', 'material' => 'Emperador', 'status' => SlabStatus::Damaged, 'location' => 'Rack B2'],
            ['code' => 'EMP-003', 'material' => 'Emperador', 'status' => SlabStatus::Missing, 'location' => 'Rack B3'],
            ['code' => 'TRA-001', 'material' => 'Travertine', 'status' => SlabStatus::Available, 'location' => 'Rack C1'],
            ['code' => 'TRA-002', 'material' => 'Travertine', 'status' => SlabStatus::Reserved, 'location' => 'Rack C2'],
            ['code' => 'TRA-003', 'material' => 'Travertine', 'status' => SlabStatus::Sold, 'location' => 'Customer'],
        ])->map(function (array $data, int $index) use ($materials, $manager): Slab {
            $material = $materials->firstWhere('name', $data['material']);

            return Slab::firstOrCreate(
                ['code' => $data['code']],
                [
                    'material_id' => $material->id,
                    'created_by_id' => $manager->id,
                    'barcode' => 'WH-'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    'length_cm' => 290 + ($index * 5),
                    'width_cm' => 150 + ($index * 2),
                    'thickness_cm' => 2,
                    'status' => $data['status'],
                    'location' => $data['location'],
                    'source' => 'Demo supplier warehouse',
                    'supplier' => 'Stone Supplier',
                    'received_at' => now()->subDays(12 - $index),
                    'shipped_at' => $data['status'] === SlabStatus::Sold ? now()->subDays(2) : null,
                    'notes' => 'Demo item for review.',
                ]
            );
        });

        $slabs->each(function (Slab $slab) use ($manager): void {
            StockMovement::firstOrCreate(
                [
                    'type' => 'item',
                    'action' => 'arrived',
                    'subject_id' => $slab->id,
                ],
                [
                    'subject_name' => $slab->code,
                    'actor_id' => $manager->id,
                    'description' => "Received item {$slab->code} into warehouse.",
                    'changes' => [
                        'location' => ['from' => '-', 'to' => $slab->location ?: '-'],
                    ],
                    'created_at' => $slab->received_at ?? now(),
                    'updated_at' => $slab->received_at ?? now(),
                ]
            );
        });

        $check = InventoryCheck::firstOrCreate(
            ['name' => 'Demo inventory check'],
            [
                'status' => 'completed',
                'started_by_id' => $manager->id,
                'completed_by_id' => $admin->id,
                'started_at' => now()->subDay(),
                'completed_at' => now()->subHours(20),
            ]
        );

        $slabs->where('status', '!=', SlabStatus::Sold)->each(function (Slab $slab) use ($check, $worker): void {
            InventoryCheckItem::firstOrCreate(
                [
                    'inventory_check_id' => $check->id,
                    'slab_id' => $slab->id,
                ],
                [
                    'expected_status' => $slab->status->value,
                    'expected_location' => $slab->location,
                    'actual_status' => $slab->status->value,
                    'actual_location' => $slab->location,
                    'result' => $slab->status === SlabStatus::Missing ? 'missing' : 'found',
                    'checked_by_id' => $worker->id,
                    'checked_at' => now()->subHours(21),
                ]
            );
        });
    }

    private function user(string $name, string $email, UserRole $role, array $extra = []): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'is_active' => true,
                ...$extra,
            ]
        );

        $user->syncRoles([$role->value]);

        return $user;
    }
}
