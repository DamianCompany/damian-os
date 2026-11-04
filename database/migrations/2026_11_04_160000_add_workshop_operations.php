<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->json('workshop_restrictions')->nullable();
        });
        Schema::create('workshop_customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type')->default('person');
            $t->string('phone')->nullable()->index();
            $t->string('document')->nullable()->index();
            $t->string('address')->nullable();
            $t->string('contact')->nullable();
            $t->string('payment_terms')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('workshop_items', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('kind');
            $t->string('category')->nullable();
            $t->string('purchase_unit')->default('unidad');
            $t->string('unit')->default('unidad');
            $t->unsignedBigInteger('conversion_milli')->default(1000);
            $t->unsignedBigInteger('quantity_milli')->default(0);
            $t->unsignedBigInteger('value_cents')->default(0);
            $t->boolean('valuation_complete')->default(true);
            $t->unsignedBigInteger('sale_price_cents')->nullable();
            $t->json('recipe')->nullable();
            $t->unsignedInteger('recipe_batch')->default(1);
            $t->boolean('favorite')->default(false);
            $t->boolean('active')->default(true);
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
        });
        Schema::create('workshop_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->bigInteger('balance_cents')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('workshop_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });
        Schema::create('workshop_rates', function (Blueprint $t) {
            $t->id();
            $t->string('kind');
            $t->string('name');
            $t->string('activity')->nullable();
            $t->foreignId('printer_id')->nullable()->constrained('printers');
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->unsignedBigInteger('hour_cents');
            $t->json('parameters');
            $t->date('effective_on');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('created_at');
            $t->index(['kind', 'effective_on']);
        });
        Schema::table('dami_orders', function (Blueprint $t) {
            $t->unsignedBigInteger('printer_id')->nullable()->change();
            $t->boolean('workshop')->default(false)->index();
            $t->foreignId('customer_id')->nullable()->constrained('workshop_customers');
            $t->string('document_kind')->default('order');
            $t->string('payment_status')->default('pending');
            $t->string('delivery_status')->default('pending');
            $t->boolean('design_required')->default(false);
            $t->boolean('design_approved')->default(false);
            $t->date('payment_due')->nullable();
            $t->string('purchase_reference')->nullable();
            $t->string('delivery_address')->nullable();
            $t->text('notes')->nullable();
            $t->json('attachments')->nullable();
            $t->unsignedBigInteger('sale_cents')->nullable();
            $t->unsignedBigInteger('collected_cents')->default(0);
            $t->unsignedInteger('version')->default(1);
        });
        Schema::create('workshop_order_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dami_order_id')->constrained()->restrictOnDelete();
            $t->foreignId('item_id')->nullable()->constrained('workshop_items');
            $t->string('description');
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_cents');
            $t->unsignedInteger('delivered')->default(0);
            $t->unsignedInteger('produced')->default(0);
            $t->json('recipe_snapshot')->nullable();
            $t->timestamps();
        });
        Schema::create('workshop_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dami_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('line_id')->nullable()->constrained('workshop_order_lines');
            $t->foreignId('item_id')->nullable()->constrained('workshop_items');
            $t->string('description');
            $t->string('purpose')->default('order');
            $t->string('activity');
            $t->foreignId('printer_id')->nullable()->constrained('printers');
            $t->foreignId('responsible_id')->constrained('users');
            $t->dateTime('planned_start')->nullable();
            $t->dateTime('planned_end')->nullable();
            $t->unsignedInteger('quantity');
            $t->boolean('final_stage')->default(false);
            $t->string('status')->default('pending');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('manual_started_at')->nullable();
            $t->unsignedBigInteger('manual_seconds')->default(0);
            $t->unsignedBigInteger('machine_seconds')->default(0);
            $t->string('file_path')->nullable();
            $t->text('incident')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
        });
        Schema::create('workshop_production', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->nullable()->unique()->constrained('workshop_tasks');
            $t->foreignId('dami_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('line_id')->nullable()->constrained('workshop_order_lines');
            $t->foreignId('item_id')->nullable()->constrained('workshop_items');
            $t->foreignId('printer_id')->nullable()->constrained('printers');
            $t->foreignId('user_id')->constrained('users');
            $t->string('description');
            $t->string('activity');
            $t->string('purpose');
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('failed')->default(0);
            $t->boolean('final_stage');
            $t->unsignedInteger('machine_minutes');
            $t->unsignedInteger('manual_minutes');
            $t->json('consumptions');
            $t->json('cost_snapshot');
            $t->unsignedBigInteger('cost_cents')->nullable();
            $t->boolean('cost_complete')->default(false);
            $t->text('incident')->nullable();
            $t->string('attachment')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('workshop_funds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('account_id')->constrained('workshop_accounts');
            $t->foreignId('dami_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('purpose');
            $t->unsignedBigInteger('assigned_cents');
            $t->unsignedBigInteger('remaining_cents');
            $t->string('status')->default('open');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('workshop_purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('supplier_id')->nullable()->constrained('proveedores_dami3d');
            $t->foreignId('dami_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('fund_id')->nullable()->constrained('workshop_funds');
            $t->foreignId('user_id')->constrained('users');
            $t->string('description');
            $t->string('category');
            $t->string('payment_source');
            $t->unsignedBigInteger('amount_cents');
            $t->unsignedBigInteger('paid_cents')->default(0);
            $t->unsignedBigInteger('reimbursed_cents')->default(0);
            $t->string('review')->default('pending');
            $t->text('review_note')->nullable();
            $t->string('receipt')->nullable();
            $t->date('purchased_on');
            $t->json('lines');
            $t->timestamps();
        });
        Schema::create('workshop_ledger', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->nullable()->constrained('workshop_accounts');
            $t->foreignId('method_id')->nullable()->constrained('workshop_methods');
            $t->foreignId('dami_order_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('purchase_id')->nullable()->constrained('workshop_purchases');
            $t->foreignId('fund_id')->nullable()->constrained('workshop_funds');
            $t->foreignId('user_id')->constrained('users');
            $t->string('kind');
            $t->bigInteger('amount_cents');
            $t->unsignedBigInteger('reversal_of')->nullable()->unique();
            $t->text('reason')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('workshop_stock', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('workshop_items');
            $t->foreignId('user_id')->constrained('users');
            $t->string('kind');
            $t->bigInteger('quantity_milli');
            $t->bigInteger('value_cents');
            $t->string('reference');
            $t->text('reason')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('workshop_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('line_id')->constrained('workshop_order_lines');
            $t->foreignId('user_id')->constrained('users');
            $t->unsignedInteger('quantity');
            $t->string('mode');
            $t->string('reference')->nullable();
            $t->unsignedBigInteger('cost_cents')->nullable();
            $t->boolean('cost_complete')->default(false);
            $t->timestamp('created_at');
        });
        Schema::create('workshop_audit', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->string('action');
            $t->string('subject');
            $t->json('changes');
            $t->timestamp('created_at');
        });
        Schema::create('workshop_corrections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('ledger_id')->constrained('workshop_ledger');
            $t->text('reason');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('workshop_operations', function (Blueprint $t) {
            $t->uuid('token')->primary();
            $t->foreignId('user_id')->constrained('users');
            $t->string('action');
            $t->string('fingerprint', 64);
            $t->json('result')->nullable();
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Operational records are preserved. Restore a verified backup to roll back.');
    }
};
