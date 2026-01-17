<?php
session_start();
// --- CAMBIO 1: El require_once ahora carga la función conectarDB() ---
require_once __DIR__ . '/../config/db_connection.php'; 

// Función para redirigir con mensajes
function redirigir($url) {
    header('Location: ' . $url);
    exit;
}

// 1. Verificar que el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// 2. VERIFICACIÓN DE PERMISOS
if (!isset($_SESSION['permisos']) || !in_array('ver_usuarios', $_SESSION['permisos'])) {
    redirigir('../index.php?page=dashboard&error=permiso');
    exit;
}

// 3. VALIDACIÓN DE TOKEN CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        redirigir('../index.php?page=usuarios&error=csrf');
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// --- CAMBIO 2: Conexión Única y Centralizada con PDO ---
try {
    $pdo = conectarDB(); // $pdo ahora es la conexión PDO
} catch (Exception $e) {
    // Si la conexión falla, se redirige con error de DB.
    redirigir('../index.php?page=usuarios&error=2');
}


// --- LÓGICA PARA CREAR O ACTUALIZAR UN USUARIO (MIGRADO A PDO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'update')) {
    
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $contrasena = $_POST['contrasena'];
    $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);

    // Validaciones básicas
    if (empty($nombre_usuario) || empty($id_rol)) {
        redirigir('../index.php?page=usuarios&error=1');
    }

    if ($action === 'create') {
        if (empty($contrasena)) {
            redirigir('../index.php?page=usuarios&error=1');
        }
        
        $hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // --- CAMBIO 3: PDO Prepare y Execute (CREATE) ---
        $sql = "INSERT INTO usuarios (nombre_usuario, contrasena, id_rol) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $ejecutado = $stmt->execute([$nombre_usuario, $hash_contrasena, $id_rol]);
    
    } else { // 'update'
        if (!empty($contrasena)) {
            $hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
            
            // --- CAMBIO 4: PDO Prepare y Execute (UPDATE con contraseña) ---
            $sql = "UPDATE usuarios SET nombre_usuario = ?, contrasena = ?, id_rol = ? WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $ejecutado = $stmt->execute([$nombre_usuario, $hash_contrasena, $id_rol, $id_usuario]);
        } else {
            // --- CAMBIO 5: PDO Prepare y Execute (UPDATE sin contraseña) ---
            $sql = "UPDATE usuarios SET nombre_usuario = ?, id_rol = ? WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            $ejecutado = $stmt->execute([$nombre_usuario, $id_rol, $id_usuario]);
        }
    }

    if ($ejecutado) {
        redirigir('../index.php?page=usuarios&success=' . ($action === 'create' ? '1' : '2'));
    } else {
        redirigir('../index.php?page=usuarios&error=2');
    }
}

// --- LÓGICA PARA BORRAR UN USUARIO (MIGRADO A PDO) ---
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    
    if ($id_usuario == $_SESSION['user_id']) {
        redirigir('../index.php?page=usuarios&error=3');
    }

    if ($id_usuario) {
        // --- CAMBIO 6: PDO Prepare y Execute (DELETE) ---
        $sql = "DELETE FROM usuarios WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $ejecutado = $stmt->execute([$id_usuario]);
        
        if ($ejecutado) {
            redirigir('../index.php?page=usuarios&success=3');
        } else {
            redirigir('../index.php?page=usuarios&error=2');
        }
    }
}

// Redirigir si no hay una acción POST válida
redirigir('../index.php?page=usuarios');
?>