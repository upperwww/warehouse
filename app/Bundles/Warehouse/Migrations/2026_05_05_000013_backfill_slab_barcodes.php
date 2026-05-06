<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('slabs', 'barcode')) {
            return;
        }

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
        //
    }
};
