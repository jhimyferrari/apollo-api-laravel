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
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('street', 45);
            $table->string('neighborhood', 45);
            $table->string('number', 10);
            $table->string('complement', 45)->nullable();
            $table->char('cep', 8);
            $table->char('city_ibge_code', 7);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('city_ibge_code')
                ->references('ibge_code')
                ->on('cities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};
