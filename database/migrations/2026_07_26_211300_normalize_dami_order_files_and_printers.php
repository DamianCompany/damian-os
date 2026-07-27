<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dami_order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dami_order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('path');
            $table->timestamps();

            $table->index(['dami_order_id', 'type']);
            $table->unique(['dami_order_id', 'type', 'path']);
        });

        Schema::create('dami_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dami_order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['dami_order_id', 'created_at']);
        });

        DB::table('dami_orders')
            ->orderBy('id')
            ->each(function (object $order): void {
                $now = now();
                $files = [];
                $references = json_decode($order->reference_images ?? '[]', true) ?: [];

                foreach ($references as $path) {
                    $files[] = [
                        'dami_order_id' => $order->id,
                        'type' => 'reference',
                        'path' => $path,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (filled($order->received_file)) {
                    $files[] = [
                        'dami_order_id' => $order->id,
                        'type' => 'received',
                        'path' => $order->received_file,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($files !== []) {
                    DB::table('dami_order_files')->insert($files);
                }

                DB::table('dami_order_status_histories')->insert([
                    'dami_order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => $order->status,
                    'changed_by' => $order->created_by,
                    'notes' => 'Estado inicial migrado',
                    'created_at' => $order->created_at ?? $now,
                ]);
            });

        Schema::table('dami_orders', function (Blueprint $table) {
            $table->dropColumn(['reference_images', 'received_file']);
        });

        Schema::table('printers', function (Blueprint $table) {
            $table->dropColumn(['model', 'responsible_name', 'next_maintenance_at']);
        });
    }

    public function down(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $table->string('model')->nullable()->after('location');
            $table->string('responsible_name')->nullable()->after('location');
            $table->date('next_maintenance_at')->nullable()->after('responsible_name');
        });

        Schema::table('dami_orders', function (Blueprint $table) {
            $table->json('reference_images')->nullable()->after('description');
            $table->string('received_file')->nullable()->after('reference_images');
        });

        DB::table('dami_orders')
            ->orderBy('id')
            ->each(function (object $order): void {
                $references = DB::table('dami_order_files')
                    ->where('dami_order_id', $order->id)
                    ->where('type', 'reference')
                    ->orderBy('id')
                    ->pluck('path')
                    ->all();
                $receivedFile = DB::table('dami_order_files')
                    ->where('dami_order_id', $order->id)
                    ->where('type', 'received')
                    ->value('path');

                DB::table('dami_orders')
                    ->where('id', $order->id)
                    ->update([
                        'reference_images' => $references === [] ? null : json_encode($references),
                        'received_file' => $receivedFile,
                    ]);
            });

        Schema::dropIfExists('dami_order_status_histories');
        Schema::dropIfExists('dami_order_files');
    }
};
