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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('type');
            $table->decimal('quantity', 15, 4);
            $table->decimal('balance_after', 15, 4);

            $table->timestamp('created_at');

            $table->uuidMorphs('reference');

            $table->foreignUuid('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');

            $table->foreignUuid('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
