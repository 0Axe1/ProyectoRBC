<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

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
$id_categoria = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
$nombre_producto = trim($_POST['nombre_producto'] ?? '');
$stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
$precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0.01]]);
$descripcion = trim($_POST['descripcion'] ?? '') ?: null;
$variedad = trim($_POST['variedad'] ?? '') ?: null;
$origen = trim($_POST['origen'] ?? '') ?: null;
$presentacion = trim($_POST['presentacion'] ?? '') ?: null;
$unidad_medida = trim($_POST['unidad_medida'] ?? '') ?: null;
$peso_neto = filter_var($_POST['peso_neto'] ?? null, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0]]);
$calidad = trim($_POST['calidad'] ?? '') ?: null;
$fecha_cosecha = trim($_POST['fecha_cosecha'] ?? '') ?: null;
$observaciones = trim($_POST['observaciones'] ?? '') ?: null;

if (empty($fecha_cosecha)) $fecha_cosecha = null;
if ($peso_neto === false || $peso_neto === '') $peso_neto = null;

try {
    if ($action === 'create') {
        if (empty($nombre_producto) || empty($id_categoria) || $stock === null || $precio === null) {
            throw new Exception('Error. Faltan datos obligatorios (Nombre, Categoría, Stock o Precio).');
        }

        $pdo->beginTransaction();
        $sql_prod = "INSERT INTO productos (id_categoria, nombre_producto, precio, stock) VALUES (?, ?, ?, ?)";
        $stmt_prod = $pdo->prepare($sql_prod);
        $stmt_prod->execute([$id_categoria, $nombre_producto, $precio, $stock]);
        $new_id_producto = $pdo->lastInsertId();

        $sql_det = "INSERT INTO detalle_producto 
            (id_producto, descripcion, variedad, origen, presentacion, unidad_medida, peso_neto, calidad, fecha_cosecha, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_det = $pdo->prepare($sql_det);
        $stmt_det->execute([
            $new_id_producto, $descripcion, $variedad, $origen, $presentacion,
            $unidad_medida, $peso_neto, $calidad, $fecha_cosecha, $observaciones
        ]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Producto y detalles registrados exitosamente.']);

    } elseif ($action === 'update' && $id_producto) {
        if (empty($nombre_producto) || empty($id_categoria) || $precio === null || $stock === null) {
            throw new Exception('Error. Faltan datos obligatorios (Nombre, Categoría, Stock o Precio).');
        }

        $pdo->beginTransaction();
        $sql_prod = "UPDATE productos SET id_categoria = ?, nombre_producto = ?, precio = ?, stock = ? WHERE id_producto = ?";
        $stmt_prod = $pdo->prepare($sql_prod);
        $stmt_prod->execute([$id_categoria, $nombre_producto, $precio, $stock, $id_producto]);

        $sql_det = "INSERT INTO detalle_producto 
            (id_producto, descripcion, variedad, origen, presentacion, unidad_medida, peso_neto, calidad, fecha_cosecha, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                descripcion = VALUES(descripcion), variedad = VALUES(variedad), origen = VALUES(origen),
                presentacion = VALUES(presentacion), unidad_medida = VALUES(unidad_medida),
                peso_neto = VALUES(peso_neto), calidad = VALUES(calidad), fecha_cosecha = VALUES(fecha_cosecha),
                observaciones = VALUES(observaciones)";
        $stmt_det = $pdo->prepare($sql_det);
        $stmt_det->execute([
            $id_producto, $descripcion, $variedad, $origen, $presentacion,
            $unidad_medida, $peso_neto, $calidad, $fecha_cosecha, $observaciones
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
    if (isset($pdo)) $pdo->rollBack();
    http_response_code(500);
    error_log("Error en inventario_actions: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar la solicitud en la base de datos.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
