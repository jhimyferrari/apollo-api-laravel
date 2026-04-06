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
        Schema::create('cities', function (Blueprint $table) {
            $table->char('ibge_code', 7)->primary();
            $table->string('name', 60);
            $table->char('uf_ibge_code', 2);

            $table->foreign('uf_ibge_code')
                ->references('ibge_code')
                ->on('ufs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
