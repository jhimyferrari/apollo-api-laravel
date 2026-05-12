<?php

namespace Database\Seeders;

use App\Models\Uf;
use Illuminate\Database\Seeder;

class UfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Adiciona ou atualiza todos os estados
        $ufs = [
            ['ibge_code' => 12, 'abbreviation' => 'AC', 'name' => 'Acre'],
            ['ibge_code' => 27, 'abbreviation' => 'AL', 'name' => 'Alagoas'],
            ['ibge_code' => 13, 'abbreviation' => 'AM', 'name' => 'Amazonas'],
            ['ibge_code' => 16, 'abbreviation' => 'AP', 'name' => 'Amapá'],
            ['ibge_code' => 29, 'abbreviation' => 'BA', 'name' => 'Bahia'],
            ['ibge_code' => 23, 'abbreviation' => 'CE', 'name' => 'Ceará'],
            ['ibge_code' => 53, 'abbreviation' => 'DF', 'name' => 'Distrito Federal'],
            ['ibge_code' => 32, 'abbreviation' => 'ES', 'name' => 'Espírito Santo'],
            ['ibge_code' => 52, 'abbreviation' => 'GO', 'name' => 'Goiás'],
            ['ibge_code' => 21, 'abbreviation' => 'MA', 'name' => 'Maranhão'],
            ['ibge_code' => 31, 'abbreviation' => 'MG', 'name' => 'Minas Gerais'],
            ['ibge_code' => 50, 'abbreviation' => 'MS', 'name' => 'Mato Grosso do Sul'],
            ['ibge_code' => 51, 'abbreviation' => 'MT', 'name' => 'Mato Grosso'],
            ['ibge_code' => 15, 'abbreviation' => 'PA', 'name' => 'Pará'],
            ['ibge_code' => 25, 'abbreviation' => 'PB', 'name' => 'Paraíba'],
            ['ibge_code' => 26, 'abbreviation' => 'PE', 'name' => 'Pernambuco'],
            ['ibge_code' => 22, 'abbreviation' => 'PI', 'name' => 'Piauí'],
            ['ibge_code' => 41, 'abbreviation' => 'PR', 'name' => 'Paraná'],
            ['ibge_code' => 33, 'abbreviation' => 'RJ', 'name' => 'Rio de Janeiro'],
            ['ibge_code' => 24, 'abbreviation' => 'RN', 'name' => 'Rio Grande do Norte'],
            ['ibge_code' => 43, 'abbreviation' => 'RS', 'name' => 'Rio Grande do Sul'],
            ['ibge_code' => 11, 'abbreviation' => 'RO', 'name' => 'Rondônia'],
            ['ibge_code' => 14, 'abbreviation' => 'RR', 'name' => 'Roraima'],
            ['ibge_code' => 42, 'abbreviation' => 'SC', 'name' => 'Santa Catarina'],
            ['ibge_code' => 28, 'abbreviation' => 'SE', 'name' => 'Sergipe'],
            ['ibge_code' => 35, 'abbreviation' => 'SP', 'name' => 'São Paulo'],
            ['ibge_code' => 17, 'abbreviation' => 'TO', 'name' => 'Tocantins'],
        ];

        foreach ($ufs as $uf) {
            Uf::updateOrCreate(
                ['ibge_code' => $uf['ibge_code']],
                values: $uf
            );
        }
    }
}
