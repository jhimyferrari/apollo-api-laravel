<?php

require __DIR__.'./../../vendor/autoload.php'; // garante autoload do Laravel, se necessário
$host = env('DB_HOST', '127.0.0.1');      // default caso não exista
$port = env('DB_PORT', 5432);
$db = env('DB_DATABASE', 'apollo_api_development');
$user = env('DB_USERNAME', 'development');
$pass = env('DB_PASSWORD', 'password');

$dsn = "pgsql:host=$host;port=$port;dbname=$db;";

echo "\n🔴 Aguardando por banco de dados - ";

while (true) {
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 1]);
        echo "\n🟢 Banco de dados aceitando conexões \n";
        break;
    } catch (PDOException $e) {
        echo '.';
        sleep(1);
    }
}
