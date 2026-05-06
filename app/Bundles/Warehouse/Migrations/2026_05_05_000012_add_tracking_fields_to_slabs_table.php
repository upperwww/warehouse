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
            $table->string('barcode')->nullable()->unique()->after('code');
            $table->string('source')->nullable()->after('location');
            $table->string('supplier')->nullable()->after('source');
            $table->timestamp('received_at')->nullable()->after('supplier');
            $table->timestamp('shipped_at')->nullable()->after('received_at');
        });

        DB::table('slabs')
            ->whereNull('barcode')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $slab): void {
                DB::table('slabs')
                    ->where('id', $slab->id)
                    ->update(['barcode' => 'WH-'.str_pad((string) $slab->id, 6, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        Schema::table('slabs', function (Blueprint $table): void {
            $table->dropUnique(['barcode']);
            $table->dropColumn([
                'barcode',
                'source',
                'supplier',
                'received_at',
                'shipped_at',
            ]);
        });
    }
};
