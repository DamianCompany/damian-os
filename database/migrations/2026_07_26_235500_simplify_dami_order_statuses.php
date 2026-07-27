<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->mapStatuses('dami_orders', 'status');
        $this->mapStatuses('dami_order_status_histories', 'from_status');
        $this->mapStatuses('dami_order_status_histories', 'to_status');
    }

    public function down(): void
    {
        DB::table('dami_orders')->where('status', 'pending')->update(['status' => 'draft']);
        DB::table('dami_orders')->where('status', 'completed')->update(['status' => 'ready']);

        foreach (['from_status', 'to_status'] as $column) {
            DB::table('dami_order_status_histories')->where($column, 'pending')->update([$column => 'draft']);
            DB::table('dami_order_status_histories')->where($column, 'completed')->update([$column => 'ready']);
        }
    }

    private function mapStatuses(string $table, string $column): void
    {
        DB::table($table)
            ->whereIn($column, ['draft', 'new', 'planned'])
            ->update([$column => 'pending']);

        DB::table($table)
            ->whereIn($column, ['review', 'blocked'])
            ->update([$column => 'in_progress']);

        DB::table($table)
            ->whereIn($column, ['ready', 'delivered'])
            ->update([$column => 'completed']);
    }
};
