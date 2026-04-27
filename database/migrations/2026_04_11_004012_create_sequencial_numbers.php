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
            $table->string('table');
            $table->bigInteger('last_number')->default(0);
            $table->unique(['organization_id', 'table']);
            $table->timestamps();
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
