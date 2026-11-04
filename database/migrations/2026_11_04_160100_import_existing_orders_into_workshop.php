<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Opening receivables, not new cash receipts. Preserve all original columns.
        DB::table('dami_orders')->where('workshop', false)->orderBy('id')->each(function ($order) {
            $customer = DB::table('workshop_customers')->where('name', $order->client_name)->first();
            $customerId = $customer?->id ?? DB::table('workshop_customers')->insertGetId([
                'name' => $order->client_name, 'type' => 'person', 'document' => $order->client_document,
                'created_by' => $order->created_by ?? DB::table('users')->orderBy('id')->value('id'), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $sale = (int) round($order->total_price * 100);
            $paid = (int) round($order->advance * 100);
            DB::table('dami_orders')->where('id', $order->id)->update(['workshop' => true, 'customer_id' => $customerId,
                'sale_cents' => $sale, 'collected_cents' => $paid, 'payment_status' => $paid >= $sale ? 'paid' : ($paid ? 'partial' : 'pending')]);
            DB::table('workshop_order_lines')->insert(['dami_order_id' => $order->id, 'description' => $order->description,
                'quantity' => $order->quantity, 'unit_cents' => (int) round($order->unit_sale_price * 100),
                'created_at' => $order->created_at, 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Imported historical orders are preserved.');
    }
};
