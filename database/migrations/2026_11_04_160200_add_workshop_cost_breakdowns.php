<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_deliveries', fn (Blueprint $t) => $t->json('cost_snapshot')->nullable());
        Schema::table('workshop_customers', fn (Blueprint $t) => $t->unsignedInteger('version')->default(1));
        Schema::create('workshop_activities', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Operational history is preserved.');
    }
};
