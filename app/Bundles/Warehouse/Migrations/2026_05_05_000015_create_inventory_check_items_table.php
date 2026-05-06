<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_check_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('slab_id')->constrained()->cascadeOnDelete();
            $table->string('expected_status');
            $table->string('expected_location')->nullable();
            $table->string('actual_status')->nullable();
            $table->string('actual_location')->nullable();
            $table->string('result')->nullable()->index();
            $table->foreignId('checked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['inventory_check_id', 'slab_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_check_items');
    }
};
