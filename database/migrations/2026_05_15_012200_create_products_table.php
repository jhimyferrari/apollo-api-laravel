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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 45);
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->bigInteger('number');
            $table->text('description')->nullable();
            $table->enum('unit', ['kg', 'un', 'cx', 'l', 'm'])->default('un');
            $table->char('ncm', 8)->nullable();
            $table->char('ean', 13)->nullable()->unique();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('sale_price', 10, 2);
            $table->decimal('stock_quantity', 10, 3)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->foreignUuid('brand_id')->nullable()
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');

            $table->foreignUuid('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->primary(['product_id', 'category_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
