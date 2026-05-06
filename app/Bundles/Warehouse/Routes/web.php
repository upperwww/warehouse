<?php

use App\Bundles\Warehouse\Livewire\Dashboard;
use App\Bundles\Warehouse\Livewire\Employees;
use App\Bundles\Warehouse\Livewire\ItemFlow;
use App\Bundles\Warehouse\Livewire\Inventory;
use App\Bundles\Warehouse\Livewire\Materials;
use App\Bundles\Warehouse\Livewire\Profile;
use App\Bundles\Warehouse\Livewire\SlabDetails;
use App\Bundles\Warehouse\Livewire\Slabs;
use App\Bundles\Warehouse\Livewire\StockMovements;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('warehouse')->name('warehouse.')->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/materials', Materials::class)->middleware('role:Admin|Manager')->name('materials');
    Route::get('/slabs', Slabs::class)->name('slabs');
    Route::get('/slabs/{slab}', SlabDetails::class)->name('slabs.show');
    Route::get('/item-flow', ItemFlow::class)->middleware('role:Admin|Manager')->name('item-flow');
    Route::get('/inventory', Inventory::class)->name('inventory');
    Route::get('/stock-movements', StockMovements::class)->middleware('role:Admin|Manager')->name('stock-movements');
    Route::get('/employees', Employees::class)->middleware('role:Admin')->name('employees');
    Route::get('/employees/{user}/profile', Profile::class)->middleware('role:Admin')->name('employees.profile');
    Route::get('/profile', Profile::class)->name('profile');
});
