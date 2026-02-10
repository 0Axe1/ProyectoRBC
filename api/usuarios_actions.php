<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

// --- Constantes de validación ---
const MIN_USERNAME_LENGTH = 3;
const MAX_USERNAME_LENGTH = 50;
const MIN_PASSWORD_LENGTH = 6;

$pdo = null;
try {
    $pdo = conectarDB();
} catch (Exception $e) {
    error_log('Error de conexión en usuarios_actions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// 1. Verificar que el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. Verificación de permisos
if (!isset($_SESSION['permisos']) || !in_array('ver_usuarios', $_SESSION['permisos'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tiene autorización para realizar esta acción.']);
    exit;
}

// 3. Validación de token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Error de seguridad al procesar la solicitud. Por favor, recargue la página e intente de nuevo.']);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' || $action === 'update') {
        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $id_rol = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);

        if (empty($nombre_usuario) || empty($id_rol)) {
            throw new Exception('Todos los campos marcados con * son requeridos.');
        }
        if (mb_strlen($nombre_usuario) < MIN_USERNAME_LENGTH || mb_strlen($nombre_usuario) > MAX_USERNAME_LENGTH) {
            throw new Exception('El nombre de usuario debe tener entre ' . MIN_USERNAME_LENGTH . ' y ' . MAX_USERNAME_LENGTH . ' caracteres.');
        }
        if ($action === 'create' && empty($contrasena)) {
            throw new Exception('La contraseña es obligatoria para crear un usuario.');
        }
        if (!empty($contrasena) && mb_strlen($contrasena) < MIN_PASSWORD_LENGTH) {
            throw new Exception('La contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.');
        }

        // Verificar nombre de usuario duplicado
        if ($action === 'create') {
            $stmt_check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? AND activo = 1");
            $stmt_check->execute([$nombre_usuario]);
        } else {
            $stmt_check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? AND id_usuario != ? AND activo = 1");
            $stmt_check->execute([$nombre_usuario, $id_usuario]);
        }
        if ($stmt_check->fetch()) {
            throw new Exception("Ya existe un usuario con el nombre '$nombre_usuario'.");
        }

        if ($action === 'create') {
            $hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre_usuario, contrasena, id_rol) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre_usuario, $hash_contrasena, $id_rol]);
            echo json_encode(['status' => 'success', 'message' => 'Usuario creado exitosamente.']);
        } else {
            if (!empty($contrasena)) {
                $hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre_usuario = ?, contrasena = ?, id_rol = ? WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre_usuario, $hash_contrasena, $id_rol, $id_usuario]);
            } else {
                $sql = "UPDATE usuarios SET nombre_usuario = ?, id_rol = ? WHERE id_usuario = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre_usuario, $id_rol, $id_usuario]);
            }
            echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado exitosamente.']);
        }
    } elseif ($action === 'delete') {
        $id_usuario = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id_usuario) {
            throw new Exception('ID de usuario no válido.');
        }
        if ($id_usuario == $_SESSION['user_id']) {
            throw new Exception('No puedes deshabilitar tu propio usuario.');
        }

        $sql = "UPDATE usuarios SET activo = 0 WHERE id_usuario = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Usuario deshabilitado exitosamente.']);
        } else {
            throw new Exception('No se pudo deshabilitar el usuario o ya estaba deshabilitado.');
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en usuarios_actions: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al procesar la solicitud en la base de datos.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
