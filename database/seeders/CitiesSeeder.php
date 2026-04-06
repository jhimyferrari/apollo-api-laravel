<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    private $delimiter = ',';

    private $enclosure = '"';

    private $escape = '\\';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/municipios.csv');
        [$header,$handle] = $this->readCSV($path);
        $this->mapAndInsertData($header, $handle);

    }

    public function readCSV($path)
    {
        if (! file_exists($path) || ! is_readable($path)) {
            $this->command->error("Arquivo CSV não encontrado ou não legível: {$path}");

            return;
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command->error('Não foi possiver abrir o CSV');

            return;
        }

        $header = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
        if ($header === false) {
            fclose($handle);
            $this->command->error('CSV vazio ou com problemar no cabeçalho');

            return;
        }

        return [$header, $handle];

    }

    public function mapAndInsertData($header, $handle)
    {
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $count = 0;

        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $data = array_combine($header, $row);

            $city = [
                'ibge_code' => $data['codigo_ibge'],
                'name' => $data['nome'],
                'uf_ibge_code' => $data['codigo_uf'],
            ];

            City::updateOrCreate([
                'codigo_ibge' => $city['codigo_ibge'],
            ], $city);
            $count++;
        }
        fclose($handle);

    }
}
