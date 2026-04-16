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
        Schema::create('sequencial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->bigInteger('last_client_number')->default(0);
            $table->unique(['organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequencial_number');
    }
};
