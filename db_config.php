<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');     
define('DB_DATABASE', 'ChambeadoresDB'); 


function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_SERVER . ";dbname=" . DB_DATABASE . ";charset=utf8",
            DB_USERNAME,
            DB_PASSWORD
        );
        // Configuración para que PDO lance excepciones ante errores SQL.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // En un entorno de producción, nunca mostrarías este mensaje de error.
        // Pero es útil para depuración.
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        return null;
    }
}