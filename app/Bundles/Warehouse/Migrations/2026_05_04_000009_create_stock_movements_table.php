<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('action')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('subject_name');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->json('changes')->nullable();
            $table->timestamps();
        });

        DB::table('slabs')->orderBy('id')->get()->each(function (object $slab): void {
            DB::table('stock_movements')->insert([
                'type' => 'item',
                'action' => 'created',
                'subject_id' => $slab->id,
                'subject_name' => $slab->code,
                'actor_id' => $slab->created_by_id,
                'description' => "Created item {$slab->code}.",
                'created_at' => $slab->created_at,
                'updated_at' => $slab->created_at,
            ]);
        });

        DB::table('materials')->orderBy('id')->get()->each(function (object $material): void {
            DB::table('stock_movements')->insert([
                'type' => 'material',
                'action' => 'created',
                'subject_id' => $material->id,
                'subject_name' => $material->name,
                'actor_id' => $material->created_by_id,
                'description' => "Created material {$material->name}.",
                'created_at' => $material->created_at,
                'updated_at' => $material->created_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
