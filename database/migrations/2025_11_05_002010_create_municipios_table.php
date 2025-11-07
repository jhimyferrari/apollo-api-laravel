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
        Schema::create('municipios', function (Blueprint $table) {
            $table->char('codigo_ibge', 7)->primary();
            $table->string('nome', 60);
            $table->char('uf_codigo_ibge', 2);

            $table->foreign('uf_codigo_ibge')
                ->references('codigo_ibge')
                ->on('ufs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
