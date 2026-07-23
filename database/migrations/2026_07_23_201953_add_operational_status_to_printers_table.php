<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->string('status')->default('available')->after('model');
            $table->string('responsible_name')->nullable()->after('location');
            $table->date('next_maintenance_at')->nullable()->after('responsible_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->dropColumn(['status', 'responsible_name', 'next_maintenance_at']);
        });
    }
};
