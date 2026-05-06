<?php

use App\Bundles\Warehouse\Utils\SlabStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slabs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->unsignedInteger('length_cm');
            $table->unsignedInteger('width_cm');
            $table->unsignedInteger('thickness_cm');
            $table->string('status')->default(SlabStatus::Available->value)->index();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slabs');
    }
};
