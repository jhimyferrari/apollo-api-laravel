<?php

namespace Database\Seeders;

use App\Models\Municipio;
use Illuminate\Database\Seeder;

class MunicipioSeeder extends Seeder
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

            $municipio = [
                'codigo_ibge' => $data['codigo_ibge'],
                'nome' => $data['nome'],
                'uf_codigo_ibge' => $data['codigo_uf'],
            ];

            Municipio::updateOrCreate([
                'codigo_ibge' => $municipio['codigo_ibge'],
            ], $municipio);
            $count++;
        }
        fclose($handle);

    }
}
