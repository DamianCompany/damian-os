<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dami_orders', function (Blueprint $table) {
            $table->boolean('requires_invoice')
                ->default(false)
                ->after('client_document');
        });
    }

    public function down(): void
    {
        Schema::table('dami_orders', function (Blueprint $table) {
            $table->dropColumn('requires_invoice');
        });
    }
};