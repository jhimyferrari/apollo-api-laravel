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
        Schema::create('ncm_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('code', 8)->unique();
            $table->text('description')->nullable();
            $table->date('valid_from');
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncm_codes');
    }
};
