<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('description');
        });

        Schema::table('slabs', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });

        Schema::table('slabs', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};
