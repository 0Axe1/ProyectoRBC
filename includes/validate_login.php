<?php
// Iniciar la sesión es lo PRIMERO que se debe hacer.
session_start();

// --- ¡NUEVO! VALIDACIÓN DE TOKEN CSRF ---
// Compara el token enviado con el guardado en la sesión.
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Si no coinciden, es un intento de CSRF o un error de sesión.
    header('Location: ../login.php?error=csrf');
    exit;
}
// --- FIN DE VALIDACIÓN CSRF ---


// --- ¡CAMBIO! UTILIZAR LA CONEXIÓN CENTRALIZADA ---
// 1. Incluir el archivo de conexión
require_once __DIR__ . '/../config/db_connection.php';

// 2. Conectar a la BD
$pdo = null;
try {
     $pdo = conectarDB(); // ¡Usar tu función!
} catch (\Exception $e) {
     // Si falla la BD, redirigir con error genérico
     // En producción, aquí se registraría el error: error_log($e->getMessage());
     error_log('Error de conexión en validate_login: ' . $e->getMessage());
     header('Location: ../login.php?error=db');
     exit;
}
// --- FIN DEL CAMBIO ---

// Verificar que los datos se enviaron por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    // No se necesita sanitizar con PDO preparado
    $username = $_POST['username'];
    $password = $_POST['password'];

    // --- ¡MODIFICADO! CONSULTA AHORA OBTIENE id_rol ---
    // Se une con 'roles' para obtener 'nombre_rol' y 'id_rol'
    $query = "SELECT
                u.id_usuario,
                u.contrasena,
                u.id_rol,
                r.nombre_rol
              FROM usuarios u
              JOIN roles r ON u.id_rol = r.id_rol
              WHERE u.nombre_usuario = ?";
   
    $stmt = $pdo->prepare($query);
    $stmt->execute([$username]);
    $usuario = $stmt->fetch(); // Obtiene el usuario o 'false' si no existe

    // --- MEJORA DE SEGURIDAD: USAR password_verify() ---
    // 1. Verifica si se encontró un usuario
    // 2. Compara el hash de la BD con el password enviado
    if ($usuario && password_verify($password, $usuario['contrasena'])) {
        // Credenciales correctas
       
        // Regenerar el ID de sesión para prevenir "session fixation"
        session_regenerate_id(true);

        // Guardar datos del usuario en la sesión
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['username'] = $username;
        $_SESSION['rol'] = $usuario['nombre_rol'];
        $_SESSION['id_rol'] = $usuario['id_rol']; // ¡NUEVO! Guardar id_rol

        // --- ¡NUEVO! CARGAR PERMISOS EN LA SESIÓN ---
        $perm_stmt = $pdo->prepare(
            "SELECT p.nombre_permiso
             FROM permisos p
             JOIN roles_permisos rp ON p.id_permiso = rp.id_permiso
             WHERE rp.id_rol = ?"
        );
        $perm_stmt->execute([$usuario['id_rol']]);
        $permisos_result = $perm_stmt->fetchAll(PDO::FETCH_COLUMN, 0); // Obtiene solo los nombres
       
        $_SESSION['permisos'] = $permisos_result; // Guardar array de permisos
        // --- FIN CARGA DE PERMISOS ---
       
        // --- ¡NUEVO! Regenerar el token CSRF después de iniciar sesión ---
        // Esto asegura que la sesión autenticada tenga un token nuevo.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // --- FIN REGENERACIÓN CSRF ---

        // Redirigir al dashboard (la ruta ../ asume que este archivo está en 'includes/')
        header('Location: ../index.php?page=dashboard');
        $pdo = null; // Cerrar conexión
        exit;
    }
}

// Si el usuario/contraseña son incorrectos o el método no es POST
$pdo = null; // Cerrar conexión
header('Location: ../login.php?error=1');
exit;
?>
