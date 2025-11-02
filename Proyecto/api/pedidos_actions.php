<?php
session_start();

// --- ¡CAMBIO! UTILIZAR LA CONEXIÓN CENTRALIZADA ---
// 1. Incluir el archivo de conexión
require_once __DIR__ . '/../config/db_connection.php';

// 2. Conectar a la BD
$pdo = null;
try {
     $pdo = conectarDB(); // ¡Usar tu función!
} catch (\Exception $e) {
    // Si la conexión falla al inicio, es un error fatal para este script
    http_response_code(500);
    // Registrar el error para depuración
    error_log('Error de conexión en pedidos_actions: ' . $e->getMessage());
    // Mensaje genérico para el cliente, ya que esta API responde JSON
    echo json_encode(['error' => 'Error de conexión a la BD.']); 
    exit;
}
// --- FIN DEL CAMBIO ---

// Función para redirigir con mensajes
function redirigir($url) {
    header('Location: ' . $url);
    exit;
}

// 1. Verificar que el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    redirigir('../login.php');
}

// 2. ¡NUEVO! VERIFICACIÓN DE PERMISOS
// Este chequeo se hace ANTES de verificar el método (POST/GET)
// para proteger TODAS las acciones, incluyendo 'get_form_data' (que es GET).
if (!isset($_SESSION['permisos']) || !in_array('ver_pedidos', $_SESSION['permisos'])) {
    // Si es una petición de API (como get_form_data), responder con JSON
    // NOTA: Esta detección de AJAX no es 100% fiable, pero es un buen estándar.
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
   
    if ($is_ajax) {
         http_response_code(403); // Forbidden
         echo json_encode(['error' => 'Acceso denegado. No tiene permisos.']);
    } else {
        // Si es un envío de formulario (POST), redirigir
        redirigir('../index.php?page=pedidos&error=permiso');
    }
    exit;
}
// --- FIN VERIFICACIÓN DE PERMISOS ---


$action = $_REQUEST['action'] ?? '';

// 3. ¡NUEVO! VALIDACIÓN DE TOKEN CSRF
// Se valida en todas las peticiones POST que modifican datos.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Token inválido o ausente
        redirigir('../index.php?page=pedidos&error=csrf');
    }
}
// --- FIN DE VALIDACIÓN CSRF ---


// --- OBTENER DATOS PARA EL MODAL (CLIENTES Y PRODUCTOS) ---
if ($action === 'get_form_data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json'); // Es una respuesta de API
    $response = ['clientes' => [], 'productos' => []];
    try {
        // Clientes (ACTIVOS)
        $clientes_sql = "SELECT id_cliente, nombre_razon_social
                         FROM clientes
                         WHERE estado = 1
                         ORDER BY nombre_razon_social ASC";
        $clientes_stmt = $pdo->query($clientes_sql);
        $response['clientes'] = $clientes_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Productos (usando la nueva columna 'cantidad_stock')
        $productos_sql = "SELECT id_producto, nombre_producto, cantidad_stock as stock
                          FROM productos
                          ORDER BY nombre_producto ASC";
        $productos_stmt = $pdo->query($productos_sql);
        $response['productos'] = $productos_stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($response);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al consultar la base de datos: ' . $e->getMessage()]);
    }
    $pdo = null;
    exit;
}

// --- LÓGICA PARA CREAR UN NUEVO PEDIDO ---
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'] ?? null;
    $fecha_cotizacion = $_POST['fecha_cotizacion'] ?? null;
    $direccion_entrega = $_POST['direccion_entrega'] ?? '';
    $detalle_json = $_POST['detalle_pedido'] ?? '[]';
    $detalle_pedido = json_decode($detalle_json, true);

    // IDs de estado asumidos (basado en el SQL que te di)
    $ID_ESTADO_PENDIENTE = 1;

    if (empty($id_cliente) || empty($fecha_cotizacion) || empty($detalle_pedido)) {
        redirigir('../index.php?page=pedidos&error=1'); // Error: Datos incompletos
    }

    try {
        $pdo->beginTransaction(); // Iniciar transacción

        // 1. Insertar el pedido principal
        // CAMBIO: 'estado' es ahora 'id_estado_pedido' y usa un ID
        $sql_pedido = "INSERT INTO pedidos (id_cliente, id_estado_pedido, fecha_cotizacion, direccion_entrega) VALUES (?, ?, ?, ?)";
        $stmt_pedido = $pdo->prepare($sql_pedido);
        $stmt_pedido->execute([$id_cliente, $ID_ESTADO_PENDIENTE, $fecha_cotizacion, $direccion_entrega]);
        $id_pedido = $pdo->lastInsertId();

        // 2. Preparar consultas para el detalle y la actualización del inventario
        $detalle_stmt = $pdo->prepare("INSERT INTO detalle_de_pedido (id_pedido, id_producto, cantidad_pedido, precio_unitario, precio_total_cotizado) VALUES (?, ?, ?, ?, ?)");
       
        // CAMBIO: Actualizar 'productos.cantidad_stock' en lugar de 'Inventario_por_Bodega'
        $stock_stmt = $pdo->prepare("UPDATE productos SET cantidad_stock = cantidad_stock - ? WHERE id_producto = ? AND cantidad_stock >= ?");
       
        foreach ($detalle_pedido as $item) {
            $id_producto = $item['id'];
            $cantidad = $item['cantidad'];
            $precio = $item['precio'];
            $subtotal = $cantidad * $precio;

            // a. Insertar cada producto en el detalle del pedido
            $detalle_stmt->execute([$id_pedido, $id_producto, $cantidad, $precio, $subtotal]);

            // b. Restar la cantidad del inventario
            $stock_stmt->execute([$cantidad, $id_producto, $cantidad]);
           
            if ($stock_stmt->rowCount() === 0) {
                // Si rowCount es 0, la condición (cantidad_stock >= ?) falló
                throw new Exception("Stock insuficiente para el producto ID: " . $id_producto);
            }
        }

        $pdo->commit();
        redirigir('../index.php?page=pedidos&success=1');

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error al crear pedido: ' . $e->getMessage());
        redirigir('../index.php?page=pedidos&error=2');
    }
    $pdo = null;
    exit;
}

// --- ¡CAMBIO! LÓGICA PARA MARCAR UN PEDIDO COMO ENTREGADO (AHORA ES POST) ---
if ($action === 'deliver' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = filter_var($_POST['id'], FILTER_VALIDATE_INT); // Se lee de POST

    // IDs de estado asumidos
    $ID_ESTADO_PENDIENTE = 1; // O 4 si es 'En Preparacion'
    $ID_ESTADO_ENTREGADO = 2;
    $ID_ESTADO_PAGADO = 2;
    $ID_METODO_PAGO_DEFAULT = 1; // Asumir 'Efectivo'

    if (!$id_pedido) {
        redirigir('../index.php?page=pedidos&error=3'); // ID inválido
    }

    try {
        $pdo->beginTransaction();

        // 1. Obtener detalles y total del pedido
        // CAMBIO: 'p.estado' es 'p.id_estado_pedido'
        $sql_get_pedido = "SELECT p.id_cliente, dp.id_producto, dp.cantidad_pedido, dp.precio_unitario
                           FROM pedidos p
                           JOIN detalle_de_pedido dp ON p.id_pedido = dp.id_pedido
                           WHERE p.id_pedido = ? AND p.id_estado_pedido IN (?, 4)"; // 1=Pendiente, 4=En Preparacion
        $stmt_get_pedido = $pdo->prepare($sql_get_pedido);
        $stmt_get_pedido->execute([$id_pedido, $ID_ESTADO_PENDIENTE]);
        $detalles = $stmt_get_pedido->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            throw new Exception("El pedido no existe o ya no está pendiente.");
        }

        // 2. Crear la venta
        $total_venta = array_sum(array_map(function($item) {
            return $item['cantidad_pedido'] * $item['precio_unitario'];
        }, $detalles));
       
        // CAMBIO: 'estado_pago' es 'id_estado_pago' y se añade 'id_metodo_pago'
        $sql_venta = "INSERT INTO ventas (id_pedido, id_metodo_pago, id_estado_pago, fecha_venta, total_venta) VALUES (?, ?, ?, NOW(), ?)";
        $stmt_venta = $pdo->prepare($sql_venta);
        // NOTA: El id_cliente ya no está en la tabla 'ventas' según el DDL. Se relaciona a través del id_pedido.
        $stmt_venta->execute([$id_pedido, $ID_METODO_PAGO_DEFAULT, $ID_ESTADO_PAGADO, $total_venta]);
        $id_venta = $pdo->lastInsertId();

        // 3. Copiar los detalles a detalle_venta
        $detalle_venta_stmt = $pdo->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario_venta, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($detalles as $item) {
            $subtotal = $item['cantidad_pedido'] * $item['precio_unitario'];
            $detalle_venta_stmt->execute([$id_venta, $item['id_producto'], $item['cantidad_pedido'], $item['precio_unitario'], $subtotal]);
        }

        // 4. Actualizar el estado del pedido
        // CAMBIO: 'estado' es 'id_estado_pedido'
        $pedido_stmt = $pdo->prepare("UPDATE pedidos SET id_estado_pedido = ? WHERE id_pedido = ?");
        $pedido_stmt->execute([$ID_ESTADO_ENTREGADO, $id_pedido]);

        $pdo->commit();
        redirigir('../index.php?page=pedidos&success=3'); // Éxito al entregar

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error al marcar como entregado: ' . $e->getMessage());
        redirigir('../index.php?page=pedidos&error=5'); // Error al procesar entrega
    }
    $pdo = null;
    exit;
}

// --- ¡CAMBIO! LÓGICA PARA CANCELAR UN PEDIDO (AHORA ES POST) ---
if ($action === 'cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = filter_var($_POST['id'], FILTER_VALIDATE_INT); // Se lee de POST
   
    // IDs de estado asumidos
    $ID_ESTADO_CANCELADO = 3;

    if (!$id_pedido) {
        redirigir('../index.php?page=pedidos&error=3'); // ID inválido
    }

    try {
        $pdo->beginTransaction();

        // 1. Obtener los detalles del pedido a cancelar
        $stmt_get_detalles = $pdo->prepare("SELECT id_producto, cantidad_pedido FROM detalle_de_pedido WHERE id_pedido = ?");
        $stmt_get_detalles->execute([$id_pedido]);
        $detalles = $stmt_get_detalles->fetchAll(PDO::FETCH_ASSOC);
       
        // 2. Devolver el stock al inventario
        // CAMBIO: Actualizar 'productos.cantidad_stock'
        $stock_stmt = $pdo->prepare("UPDATE productos SET cantidad_stock = cantidad_stock + ? WHERE id_producto = ?");
        foreach ($detalles as $item) {
            $stock_stmt->execute([$item['cantidad_pedido'], $item['id_producto']]);
        }
       
        // 3. Actualizar el estado del pedido
        // CAMBIO: 'estado' es 'id_estado_pedido'
        $pedido_stmt = $pdo->prepare("UPDATE pedidos SET id_estado_pedido = ? WHERE id_pedido = ?");
        $pedido_stmt->execute([$ID_ESTADO_CANCELADO, $id_pedido]);

        $pdo->commit();
        redirigir('../index.php?page=pedidos&success=2');

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error al cancelar pedido: ' . $e->getMessage());
        redirigir('../index.php?page=pedidos&error=4');
    }
    $pdo = null;
    exit;
}

// Si ninguna acción coincide
$pdo = null;
http_response_code(400); // Bad Request
echo "Acción no válida o no especificada.";
exit;
?>
