<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slabs', function (Blueprint $table): void {
            $table->foreignId('created_by_id')
                ->nullable()
                ->after('material_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $adminId = DB::table('users')->where('email', 'admin@example.com')->value('id');

        if ($adminId) {
            DB::table('slabs')->whereNull('created_by_id')->update(['created_by_id' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('slabs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_id');
        });
    }
};
