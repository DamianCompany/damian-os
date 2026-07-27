<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dami_orders', function (Blueprint $table): void {
            $table->decimal('postprocess_hours', 8, 2)->nullable()->after('print_hours');
            $table->dropColumn('postprocess_date');
        });
    }

    public function down(): void
    {
        Schema::table('dami_orders', function (Blueprint $table): void {
            $table->date('postprocess_date')->nullable()->after('print_hours');
            $table->dropColumn('postprocess_hours');
        });
    }
};
