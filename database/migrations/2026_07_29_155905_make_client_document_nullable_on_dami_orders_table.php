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
            $table->string('client_document', 20)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('dami_orders')
            ->whereNull('client_document')
            ->update(['client_document' => '']);

        Schema::table('dami_orders', function (Blueprint $table) {
            $table->string('client_document', 20)
                ->nullable(false)
                ->change();
        });
    }
};