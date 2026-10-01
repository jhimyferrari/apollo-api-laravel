<?php

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('number');
            $table->string('status')->default(OrderStatus::Draft->value);
            $table->string('payment_status')->default(PaymentStatus::Pending->value);

            $table->decimal('total', 15, 4)->default(0);

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->softDeletes();

            $table->foreignUuid('supplier_id')
                ->references('id')
                ->on('suppliers')
                ->restrictOnDelete();

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
        Schema::dropIfExists('purchase_orders');
    }
};
