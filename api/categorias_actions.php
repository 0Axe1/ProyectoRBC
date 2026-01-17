<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$pdo = null;
try {
     $pdo = conectarDB();
} catch (\Exception $e) {
    error_log('Error de conexión en categorias_actions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// 1. Verificar si ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. VERIFICACIÓN DE PERMISOS
if (!isset($_SESSION['permisos']) || !in_array('ver_categorias', $_SESSION['permisos'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción.']);
    exit;
}

// 3. VALIDACIÓN DE TOKEN CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Error de seguridad (CSRF). Por favor, recargue la página.']);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';

// OBTENER DATOS
$nombre = trim($_POST['nombre_categoria'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '') ?: null;
$id_categoria = filter_var($_POST['id_categoria'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);

try {
    if ($action === 'create' || $action === 'update') {
        if (empty($nombre)) {
            throw new Exception('El nombre de la categoría es obligatorio.');
        }
    }

    if ($action === 'create') {
        $sql = "INSERT INTO categorias_producto (nombre_categoria, descripcion) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $descripcion]);
        
        echo json_encode(['status' => 'success', 'message' => 'Categoría agregada exitosamente.']);

    } elseif ($action === 'update' && $id_categoria) {
        $sql = "UPDATE categorias_producto SET nombre_categoria = ?, descripcion = ? WHERE id_categoria = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $id_categoria]);
        
        echo json_encode(['status' => 'success', 'message' => 'Categoría actualizada exitosamente.']);

    } elseif ($action === 'delete' && $id_categoria) {
        
        try {
            $sql = "DELETE FROM categorias_producto WHERE id_categoria = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_categoria]);
        } catch (PDOException $e) {
            // Capturar error de llave foránea (Código 23000)
            if ($e->getCode() == '23000') {
                 throw new Exception('No se puede eliminar la categoría porque está siendo usada por uno o más productos.');
            } else {
                throw $e; // Lanzar otros errores de BD
            }
        }

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Categoría eliminada exitosamente.']);
        } else {
            throw new Exception('No se pudo encontrar la categoría a eliminar.');
        }

    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o faltan datos.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en categorias_actions: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
?>