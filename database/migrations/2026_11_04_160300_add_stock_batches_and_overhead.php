<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_batches', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('item_id')->constrained('workshop_items');
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('completed')->default(0);
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
        Schema::table('workshop_tasks', fn (Blueprint $t) => $t->foreignId('batch_id')->nullable()->constrained('workshop_batches'));
        Schema::table('workshop_production', fn (Blueprint $t) => $t->foreignId('batch_id')->nullable()->constrained('workshop_batches'));
        Schema::table('workshop_items', fn (Blueprint $t) => $t->unsignedBigInteger('overhead_value_cents')->default(0));
        Schema::table('workshop_stock', fn (Blueprint $t) => $t->bigInteger('overhead_value_cents')->default(0));
    }

    public function down(): void
    {
        throw new RuntimeException('Stock history is preserved.');
    }
};
