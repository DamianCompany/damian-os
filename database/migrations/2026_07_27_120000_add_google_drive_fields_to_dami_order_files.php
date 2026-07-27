<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dami_order_files', function (Blueprint $table): void {
            $table->string('google_drive_file_id')->nullable()->unique()->after('path');
            $table->text('google_drive_url')->nullable()->after('google_drive_file_id');
            $table->string('google_drive_folder_id')->nullable()->after('google_drive_url');
            $table->timestamp('synced_to_drive_at')->nullable()->after('google_drive_folder_id');
        });
    }

    public function down(): void
    {
        Schema::table('dami_order_files', function (Blueprint $table): void {
            $table->dropUnique(['google_drive_file_id']);
            $table->dropColumn([
                'google_drive_file_id',
                'google_drive_url',
                'google_drive_folder_id',
                'synced_to_drive_at',
            ]);
        });
    }
};
