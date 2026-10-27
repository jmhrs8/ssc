<?php
// models/Usuario.php

namespace Models;

use Config\Database;
use PDO;

class Usuario 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = Database::getInstance();
    }

    public function obtenerPorEmail(string $email): ?array 
    {
        $sql = "SELECT u.*, r.nombre AS rol_nombre 
                FROM usuarios u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE u.email = :email AND u.activo = 1 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }
}
