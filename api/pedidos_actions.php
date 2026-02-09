<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$pdo = null;
try {
     $pdo = conectarDB();
} catch (\Exception $e) {
    error_log('Error de conexión en pedidos_actions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}
if (!isset($_SESSION['permisos']) || !in_array('ver_pedidos', $_SESSION['permisos'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Error de seguridad (CSRF). Por favor, recargue la página.']);
        exit;
    }
}


$action = $_REQUEST['action'] ?? '';

try {
    // --- OBTENER DATOS PARA EL MODAL (CLIENTES Y PRODUCTOS) ---
    if ($action === 'get_form_data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $response = ['clientes' => [], 'productos' => []];
        
        $clientes_sql = "SELECT id_cliente, nombre_razon_social FROM clientes WHERE estado = 1 ORDER BY nombre_razon_social ASC";
        $response['clientes'] = $pdo->query($clientes_sql)->fetchAll(PDO::FETCH_ASSOC);

        $productos_sql = "SELECT 
                            p.id_producto, 
                            CONCAT(
                                p.nombre_producto, 
                                ' (', 
                                COALESCE(dp.variedad, 'Genérico'), 
                                ', ', 
                                COALESCE(dp.presentacion, 'N/A'), 
                                ')'
                            ) AS nombre_descriptivo,
                            p.stock, 
                            p.precio 
                          FROM productos p
                          LEFT JOIN detalle_producto dp ON p.id_producto = dp.id_producto
                          WHERE p.activo = 1 ORDER BY p.nombre_producto ASC";
        $response['productos'] = $pdo->query($productos_sql)->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($response);
    
    // --- OBTENER DETALLES DE UN PEDIDO PARA EDITAR/VER ---
    } elseif ($action === 'get_order_details' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id_pedido) {
             throw new Exception('ID de pedido no válido.', 400);
        }

        $sql_head = "SELECT id_pedido, id_cliente, fecha_cotizacion, direccion_entrega FROM pedidos WHERE id_pedido = ?";
        $stmt_head = $pdo->prepare($sql_head);
        $stmt_head->execute([$id_pedido]);
        $head = $stmt_head->fetch(PDO::FETCH_ASSOC);

        if (!$head) {
             throw new Exception('Pedido no encontrado.', 404);
        }

        $sql_details = "SELECT 
                            dp.id_producto, 
                            CONCAT(
                                p.nombre_producto, 
                                ' (', 
                                COALESCE(dprod.variedad, 'Genérico'), 
                                ', ', 
                                COALESCE(dprod.presentacion, 'N/A'), 
                                ')'
                            ) AS nombre_producto,
                            dp.cantidad_pedido, 
                            dp.precio_unitario
                        FROM detalle_de_pedido dp
                        JOIN productos p ON dp.id_producto = p.id_producto
                        LEFT JOIN detalle_producto dprod ON p.id_producto = dprod.id_producto
                        WHERE dp.id_pedido = ?";
        $stmt_details = $pdo->prepare($sql_details);
        $stmt_details->execute([$id_pedido]);
        $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['head' => $head, 'details' => $details]);

    // --- CREAR UN NUEVO PEDIDO ---
    } elseif ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
        $fecha_cotizacion = $_POST['fecha_cotizacion'] ?? null;
        $direccion_entrega = trim($_POST['direccion_entrega'] ?? '');
        $detalle_json = $_POST['detalle_pedido'] ?? '[]';
        $detalle_pedido = json_decode($detalle_json, true);
        $ID_ESTADO_PENDIENTE = 1;

        if (empty($id_cliente)) {
            throw new Exception('Debe seleccionar un cliente.', 400);
        }
        if (empty($fecha_cotizacion)) {
            throw new Exception('Debe seleccionar una fecha de cotización.', 400);
        }
        // Validación de formato de fecha
        if (!DateTime::createFromFormat('Y-m-d', $fecha_cotizacion)) {
            throw new Exception('Formato de fecha de cotización no válido.', 400);
        }
        // Validación: la fecha de cotización no debe ser futura
        if (strtotime($fecha_cotizacion) > strtotime(date('Y-m-d'))) {
            throw new Exception('La fecha de cotización no puede ser futura.', 400);
        }

        // Validación de dirección de entrega
        if (empty($direccion_entrega)) {
            throw new Exception('La dirección de entrega es obligatoria.', 400);
        }
        if (strlen($direccion_entrega) < 5 || strlen($direccion_entrega) > 255) {
            throw new Exception('La dirección de entrega debe tener entre 5 y 255 caracteres.', 400);
        }

        if (empty($detalle_pedido) || !is_array($detalle_pedido)) {
            throw new Exception('Debe agregar al menos un producto al pedido.', 400);
        }

        $pdo->beginTransaction();

        $sql_pedido = "INSERT INTO pedidos (id_cliente, id_estado_pedido, fecha_cotizacion, direccion_entrega) VALUES (?, ?, ?, ?)";
        $stmt_pedido = $pdo->prepare($sql_pedido);
        $stmt_pedido->execute([$id_cliente, $ID_ESTADO_PENDIENTE, $fecha_cotizacion, $direccion_entrega]);
        $id_pedido = $pdo->lastInsertId();

        $detalle_stmt = $pdo->prepare("INSERT INTO detalle_de_pedido (id_pedido, id_producto, cantidad_pedido, precio_unitario, precio_total_cotizado) VALUES (?, ?, ?, ?, ?)");
        
        $stock_stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ? AND stock >= ?");
       
        foreach ($detalle_pedido as $item) {
            $id_producto = filter_var($item['id'], FILTER_VALIDATE_INT);
            $cantidad = filter_var($item['cantidad'], FILTER_VALIDATE_INT);
            $precio = filter_var($item['precio'], FILTER_VALIDATE_FLOAT);
            
            if (!$id_producto || !$cantidad || !$precio || $cantidad <= 0 || $precio <= 0) {
                 throw new Exception('Detalle de producto no válido (ID, cantidad o precio).', 400);
            }
            $subtotal = $cantidad * $precio;

            // Verificar stock
            $stock_stmt->execute([$cantidad, $id_producto, $cantidad]);
            if ($stock_stmt->rowCount() === 0) {
                $prod_stmt = $pdo->prepare("SELECT nombre_producto FROM productos WHERE id_producto = ?");
                $prod_stmt->execute([$id_producto]);
                $prod_nombre = $prod_stmt->fetchColumn() ?? 'ID ' . $id_producto;
                throw new Exception("Stock insuficiente para el producto: " . $prod_nombre, 400);
            }
            
            $detalle_stmt->execute([$id_pedido, $id_producto, $cantidad, $precio, $subtotal]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pedido creado exitosamente.']);

    // --- ACTUALIZAR UN PEDIDO (SOLO DATOS GENERALES) ---
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_pedido = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);
        $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
        $fecha_cotizacion = $_POST['fecha_cotizacion'] ?? null;
        $direccion_entrega = trim($_POST['direccion_entrega'] ?? '');
        $ID_ESTADO_PENDIENTE = 1;

        if (empty($id_pedido)) {
            throw new Exception('ID de pedido no válido.', 400);
        }
        if (empty($id_cliente)) {
            throw new Exception('Debe seleccionar un cliente.', 400);
        }
        if (empty($fecha_cotizacion)) {
            throw new Exception('Debe seleccionar una fecha de cotización.', 400);
        }
        // Validación de formato de fecha
        if (!DateTime::createFromFormat('Y-m-d', $fecha_cotizacion)) {
            throw new Exception('Formato de fecha de cotización no válido.', 400);
        }
        // Validación: la fecha de cotización no debe ser futura
        if (strtotime($fecha_cotizacion) > strtotime(date('Y-m-d'))) {
            throw new Exception('La fecha de cotización no puede ser futura.', 400);
        }

        // Validación de dirección de entrega
        if (empty($direccion_entrega)) {
            throw new Exception('La dirección de entrega es obligatoria.', 400);
        }
        if (strlen($direccion_entrega) < 5 || strlen($direccion_entrega) > 255) {
            throw new Exception('La dirección de entrega debe tener entre 5 y 255 caracteres.', 400);
        }

        $sql = "UPDATE pedidos SET id_cliente = ?, fecha_cotizacion = ?, direccion_entrega = ? 
                WHERE id_pedido = ? AND id_estado_pedido = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente, $fecha_cotizacion, $direccion_entrega, $id_pedido, $ID_ESTADO_PENDIENTE]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No se pudo actualizar el pedido. Es posible que ya no esté en estado "Pendiente", no se encontraron cambios o el pedido no existe.', 404);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Pedido actualizado exitosamente.']);

    // --- MARCAR PEDIDO COMO ENTREGADO ---
    } elseif ($action === 'deliver' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_pedido = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $ID_ESTADO_PENDIENTE = 1;
        $ID_ESTADO_EN_PREPARACION = 4;
        $ID_ESTADO_ENTREGADO = 2;
        $ID_ESTADO_PAGADO = 2;
        $ID_METODO_PAGO_DEFAULT = 1; // Asume 'Efectivo'

        if (!$id_pedido) {
            throw new Exception('ID de pedido no válido.', 400);
        }

        $pdo->beginTransaction();

        $sql_get_pedido = "SELECT dp.id_producto, dp.cantidad_pedido, dp.precio_unitario
                           FROM pedidos p
                           JOIN detalle_de_pedido dp ON p.id_pedido = dp.id_pedido
                           WHERE p.id_pedido = ? AND p.id_estado_pedido IN (?, ?)";
        $stmt_get_pedido = $pdo->prepare($sql_get_pedido);
        $stmt_get_pedido->execute([$id_pedido, $ID_ESTADO_PENDIENTE, $ID_ESTADO_EN_PREPARACION]);
        $detalles = $stmt_get_pedido->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            throw new Exception("El pedido no existe o ya no está pendiente/en preparación.", 404);
        }

        $total_venta = array_sum(array_map(fn($item) => $item['cantidad_pedido'] * $item['precio_unitario'], $detalles));
       
        $sql_venta = "INSERT INTO ventas (id_pedido, id_metodo_pago, id_estado_pago, fecha_venta, total_venta) VALUES (?, ?, ?, NOW(), ?)";
        $stmt_venta = $pdo->prepare($sql_venta);
        $stmt_venta->execute([$id_pedido, $ID_METODO_PAGO_DEFAULT, $ID_ESTADO_PAGADO, $total_venta]);
        $id_venta = $pdo->lastInsertId();

        $detalle_venta_stmt = $pdo->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario_venta, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($detalles as $item) {
            $subtotal = $item['cantidad_pedido'] * $item['precio_unitario'];
            $detalle_venta_stmt->execute([$id_venta, $item['id_producto'], $item['cantidad_pedido'], $item['precio_unitario'], $subtotal]);
        }

        $pedido_stmt = $pdo->prepare("UPDATE pedidos SET id_estado_pedido = ? WHERE id_pedido = ?");
        $pedido_stmt->execute([$ID_ESTADO_ENTREGADO, $id_pedido]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pedido marcado como Entregado. Se generó la venta.']);

    // --- CANCELAR UN PEDIDO ---
    } elseif ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_pedido = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $ID_ESTADO_PENDIENTE = 1;
        $ID_ESTADO_EN_PREPARACION = 4;
        $ID_ESTADO_CANCELADO = 3;

        if (!$id_pedido) {
            throw new Exception('ID de pedido no válido.', 400);
        }

        $pdo->beginTransaction();
        
        $sql_check = "SELECT id_estado_pedido FROM pedidos WHERE id_pedido = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_pedido]);
        $estado_actual = $stmt_check->fetchColumn();

        if ($estado_actual != $ID_ESTADO_PENDIENTE && $estado_actual != $ID_ESTADO_EN_PREPARACION) {
            throw new Exception('No se puede cancelar el pedido (ya fue entregado o cancelado).', 400);
        }

        $stmt_get_detalles = $pdo->prepare("SELECT id_producto, cantidad_pedido FROM detalle_de_pedido WHERE id_pedido = ?");
        $stmt_get_detalles->execute([$id_pedido]);
        $detalles = $stmt_get_detalles->fetchAll(PDO::FETCH_ASSOC);
       
        $stock_stmt = $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
        foreach ($detalles as $item) {
            $stock_stmt->execute([$item['cantidad_pedido'], $item['id_producto']]);
        }
       
        $pedido_stmt = $pdo->prepare("UPDATE pedidos SET id_estado_pedido = ? WHERE id_pedido = ?");
        $pedido_stmt->execute([$ID_ESTADO_CANCELADO, $id_pedido]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pedido cancelado exitosamente. El stock ha sido restaurado.']);

    } else {
        throw new Exception('Acción no válida o no especificada.', 400);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    error_log("Error en pedidos_actions: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
?>