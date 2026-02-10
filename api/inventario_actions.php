<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

// --- Constantes ---
const LOW_STOCK_THRESHOLD = 50;
const MAX_NOMBRE_LENGTH = 255;
const MAX_DESCRIPCION_LENGTH = 1000;

$pdo = null;
try {
    $pdo = conectarDB();
} catch (\Exception $e) {
    error_log('Error de conexión en inventario_actions: ' . $e->getMessage());
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

// 2. Verificación de permisos
if (!isset($_SESSION['permisos']) || !in_array('ver_inventario', $_SESSION['permisos'])) {
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
$id_producto = filter_var($_POST['id_producto'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);

$nombre_producto = trim($_POST['nombre_producto'] ?? '');
$stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
$precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0.01]]);
$descripcion = trim($_POST['descripcion'] ?? '') ?: null;
$unidad_medida = trim($_POST['unidad_medida'] ?? '') ?: null;
$peso_neto = filter_var($_POST['peso_neto'] ?? null, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0]]);
$link_documentos = trim($_POST['link_documentos'] ?? '') ?: null;

if ($peso_neto === false || $peso_neto === '') $peso_neto = null;

try {
    if ($action === 'create') {
        if (empty($nombre_producto) || $stock === false || $precio === false) {
            throw new Exception('Error. Faltan datos obligatorios o los valores son incorrectos (Nombre, Stock >= 0, Precio >= 0.01).');
        }
        if (mb_strlen($nombre_producto) > MAX_NOMBRE_LENGTH) {
            throw new Exception('El nombre del producto no puede exceder ' . MAX_NOMBRE_LENGTH . ' caracteres.');
        }
        if ($descripcion !== null && mb_strlen($descripcion) > MAX_DESCRIPCION_LENGTH) {
            throw new Exception('La descripción no puede exceder ' . MAX_DESCRIPCION_LENGTH . ' caracteres.');
        }
        if ($link_documentos !== null && !filter_var($link_documentos, FILTER_VALIDATE_URL)) {
            throw new Exception('El link de documentos debe ser una URL válida (ej: https://...).');
        }

        $pdo->beginTransaction();

        // Verificar si ya existe un producto con el mismo nombre
        $stmt_check = $pdo->prepare("SELECT id_producto FROM productos WHERE nombre_producto = ? AND activo = 1");
        $stmt_check->execute([$nombre_producto]);
        if ($stmt_check->fetch()) {
            $pdo->rollBack();
            throw new Exception("Ya existe un producto registrado con el nombre '$nombre_producto'.");
        }


        $sql_prod = "INSERT INTO productos (nombre_producto, precio, stock) VALUES (?, ?, ?)";
        $stmt_prod = $pdo->prepare($sql_prod);
        $stmt_prod->execute([$nombre_producto, $precio, $stock]);
        $new_id_producto = $pdo->lastInsertId();

        $sql_det = "INSERT INTO detalle_producto 
            (id_producto, descripcion, unidad_medida, peso_neto, link_documentos)
            VALUES (?, ?, ?, ?, ?)";
        $stmt_det = $pdo->prepare($sql_det);
        $stmt_det->execute([
            $new_id_producto, $descripcion, $unidad_medida, $peso_neto, $link_documentos
        ]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Producto y detalles registrados exitosamente.']);

    } elseif ($action === 'update' && $id_producto) {
        if (empty($nombre_producto) || $precio === false || $stock === false) {
            throw new Exception('Error. Faltan datos obligatorios o los valores son incorrectos (Nombre, Stock >= 0, Precio >= 0.01).');
        }
        if (mb_strlen($nombre_producto) > MAX_NOMBRE_LENGTH) {
            throw new Exception('El nombre del producto no puede exceder ' . MAX_NOMBRE_LENGTH . ' caracteres.');
        }
        if ($descripcion !== null && mb_strlen($descripcion) > MAX_DESCRIPCION_LENGTH) {
            throw new Exception('La descripción no puede exceder ' . MAX_DESCRIPCION_LENGTH . ' caracteres.');
        }
        if ($link_documentos !== null && !filter_var($link_documentos, FILTER_VALIDATE_URL)) {
            throw new Exception('El link de documentos debe ser una URL válida (ej: https://...).');
        }

        $pdo->beginTransaction();

        // Verificar si ya existe otro producto con el mismo nombre
        $stmt_check = $pdo->prepare("SELECT id_producto FROM productos WHERE nombre_producto = ? AND id_producto != ? AND activo = 1");
        $stmt_check->execute([$nombre_producto, $id_producto]);
        if ($stmt_check->fetch()) {
            $pdo->rollBack();
            throw new Exception("Ya existe otro producto registrado con el nombre '$nombre_producto'.");
        }


        $sql_prod = "UPDATE productos SET nombre_producto = ?, precio = ?, stock = ? WHERE id_producto = ?";
        $stmt_prod = $pdo->prepare($sql_prod);
        $stmt_prod->execute([$nombre_producto, $precio, $stock, $id_producto]);

        $sql_det = "INSERT INTO detalle_producto 
            (id_producto, descripcion, unidad_medida, peso_neto, link_documentos)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                descripcion = VALUES(descripcion),
                unidad_medida = VALUES(unidad_medida),
                peso_neto = VALUES(peso_neto),
                link_documentos = VALUES(link_documentos)";
        $stmt_det = $pdo->prepare($sql_det);
        $stmt_det->execute([
            $id_producto, $descripcion, $unidad_medida, $peso_neto, $link_documentos
        ]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Producto y detalles actualizados exitosamente.']);

    } elseif ($action === 'delete' && $id_producto) {
        $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id_producto = ?");
        $stmt->execute([$id_producto]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Producto desactivado del inventario.']);
        } else {
            throw new Exception('No se pudo encontrar el producto a eliminar.');
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o faltan datos.']);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Error en inventario_actions: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar la solicitud en la base de datos.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
