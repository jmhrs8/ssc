<?php
// config/Database.php

namespace Config;

use PDO;
use PDOException;

class Database 
{
    private static ?PDO $instance = null;

    private string $host = '127.0.0.1';
    private string $db   = 'pos_hibrido';
    private string $user = 'root';
    private string $pass = 'jmhl2474';
    private string $charset = 'utf8mb4';

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO 
    {
        if (self::$instance === null) {
            $dbObj = new self();
            self::$instance = $dbObj->connect();
        }
        return self::$instance;
    }

    private function connect(): PDO 
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Utiliza Prepared Statements nativos
        ];

        try {
            return new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // En producción, registra en un log y muestra mensaje genérico
            error_log("Error de conexión SQL: " . $e->getMessage());
            throw new PDOException("Error al conectar con la base de datos.");
        }
    }
}
