<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->string('color', 20)->nullable()->after('description');
        });
    }
};
