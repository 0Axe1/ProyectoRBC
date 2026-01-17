<?php

/**
 * ¡MODIFICADO!
 * Establece y retorna una conexión PDO a la base de datos.
 *
 * @return PDO El objeto de conexión PDO en caso de éxito.
 * @throws PDOException Si la conexión a la base de datos falla.
 */
function conectarDB() {
    $servidor = 'localhost';
    $nombre_db = 'db_rbc_3nf';
    $usuario = 'root';
    $contrasena = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$servidor;dbname=$nombre_db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $usuario, $contrasena, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // En un entorno de producción, registrarías este error en un archivo de log.
        // error_log('Error de conexión a la base de datos: ' . $e->getMessage());
        
        // Lanzar la excepción permite que el script que llama la atrape.
        throw new Exception('No se pudo conectar a la base de datos. Revisa las credenciales en db_connection.php');
    }
}

/**
 * --- FUNCIÓN DE AYUDA PARA XSS ---
 * Escapa una cadena de texto para mostrarla de forma segura en HTML.
 *
 * @param string|null $string La cadena a escapar.
 * @return string La cadena escapada.
 */
function e($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

?>

