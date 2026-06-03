<?php
/*
 * configuracion/conexion.php
 * ---------------------------------------------------------------
 * Archivo central de conexión a la base de datos.
 * Usa PDO — una forma moderna y segura de conectarse a MySQL.
 * Se incluye con require_once en todos los archivos que usen DB.
 * ---------------------------------------------------------------
 */

define('DB_HOST',    'localhost');
define('DB_NOMBRE',  'mediturnos');
define('DB_USUARIO', 'root');
define('DB_CLAVE',   '');   

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NOMBRE . ";charset=utf8mb4",
        DB_USUARIO,
        DB_CLAVE,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}