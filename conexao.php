<?php

define('PG_HOST',     getenv('PG_HOST')     ?: 'localhost');
define('PG_PORT',     getenv('PG_PORT')     ?: '5432');
define('PG_USER',     getenv('PG_USER')     ?: 'postgres');
define('PG_PASSWORD', getenv('PG_PASSWORD') ?: '');
define('PG_DB_NAME',  getenv('PG_DB_NAME')  ?: 'moradores_de_rua');

try {
    $dsn  = 'pgsql:host=' . PG_HOST . ';port=' . PG_PORT . ';dbname=' . PG_DB_NAME;
    $conn = new PDO($dsn, PG_USER, PG_PASSWORD, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $conn->exec("SET client_encoding TO 'UTF8'");
} catch (PDOException $e) {
    echo 'Erro ao conectar com o PostgreSQL: ' . $e->getMessage();
}
?>
