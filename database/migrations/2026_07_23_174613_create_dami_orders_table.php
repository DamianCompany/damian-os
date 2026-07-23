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
        Schema::create('dami_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedInteger('quantity');
            $table->string('client_name');
            $table->string('client_document', 20);
            $table->text('description');
            $table->json('reference_images')->nullable();
            $table->string('received_file')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('delivery_date');
            $table->decimal('filament_grams', 10, 2);
            $table->string('filament_type', 10);
            $table->decimal('print_hours', 8, 2)->default(0);
            $table->decimal('postprocess_hours', 8, 2)->default(0);
            $table->decimal('filament_cost', 10, 2)->default(0);
            $table->decimal('electricity_cost', 10, 2)->default(0);
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('unit_sale_price', 10, 2);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('profit', 10, 2)->default(0);
            $table->decimal('advance', 10, 2)->default(0);
            $table->decimal('pending_balance', 10, 2)->default(0);
            $table->string('responsible_name');
            $table->foreignId('printer_id')->constrained()->restrictOnDelete();
            $table->string('printer_location');
            $table->string('authorized_responsible');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dami_orders');
    }
};
