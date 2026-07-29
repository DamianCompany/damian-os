<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dami_orders', function (Blueprint $table) {
            $table->renameColumn('print_hours', 'print_minutes');
        });

        DB::table('dami_orders')->update([
            'print_minutes' => DB::raw('ROUND(print_minutes * 60)'),
        ]);

        Schema::table('dami_orders', function (Blueprint $table) {
            $table->unsignedInteger('print_minutes')
                ->default(0)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('dami_orders', function (Blueprint $table) {
            $table->decimal('print_minutes', 8, 2)
                ->default(0)
                ->change();
        });

        DB::table('dami_orders')->update([
            'print_minutes' => DB::raw('print_minutes / 60'),
        ]);

        Schema::table('dami_orders', function (Blueprint $table) {
            $table->renameColumn('print_minutes', 'print_hours');
        });
    }
};