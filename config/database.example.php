<?php
// config/database.php
function getDatabaseConnection()
{
    $host = 'localhost';
    $dbname = 'DB_NAME_HERE';
    $username = 'USER_HERE';
    $password = 'PASSWORD_HERE';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // En producción: loggear el error y mostrar mensaje genérico
        error_log("Database connection failed: " . $e->getMessage());
        die("Error al conectar con la base de datos. Por favor, inténtalo más tarde.");
    }
}