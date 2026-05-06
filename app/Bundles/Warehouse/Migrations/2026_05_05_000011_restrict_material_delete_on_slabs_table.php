<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slabs', function (Blueprint $table): void {
            $table->dropForeign(['material_id']);
            $table->foreign('material_id')->references('id')->on('materials')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('slabs', function (Blueprint $table): void {
            $table->dropForeign(['material_id']);
            $table->foreign('material_id')->references('id')->on('materials')->cascadeOnDelete();
        });
    }
};
