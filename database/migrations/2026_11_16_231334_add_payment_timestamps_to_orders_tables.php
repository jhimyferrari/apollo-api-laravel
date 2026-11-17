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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'refunded_at']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'refunded_at']);
        });
    }
};
