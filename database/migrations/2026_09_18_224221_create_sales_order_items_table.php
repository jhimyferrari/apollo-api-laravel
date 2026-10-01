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
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('number');
            $table->foreignUuid('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->restrictOnDelete();

            $table->foreignUuid('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('subtotal', 15, 4);

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
