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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('number');
            $table->enum('status', ['active', 'inactive']);
            $table->string('document');
            $table->string('legal_name');
            $table->string('trade_name');
            $table->string('state_registration')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();

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
        Schema::dropIfExists('clients');
    }
};
