<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_order_lines', fn (Blueprint $t) => $t->boolean('superseded')->default(false));
    }

    public function down(): void
    {
        throw new RuntimeException('Quote revisions are preserved.');
    }
};
