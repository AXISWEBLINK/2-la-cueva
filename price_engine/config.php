<?php

const APP_DB_HOST = '66.97.41.59';
const APP_DB_NAME = 'soft_lacueva1';
const APP_DB_USER = 'lacuev_master';
const APP_DB_PASS = '7S9H*LF!Vsu(lMNP';
const APP_DB_PORT = 3306;

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    APP_DB_HOST,
    APP_DB_PORT,
    APP_DB_NAME
);

try {
    $pdo = new PDO(
        $dsn,
        APP_DB_USER,
        APP_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    throw new RuntimeException(
        'Error de conexion MySQL: ' . $exception->getMessage(),
        0,
        $exception
    );
}