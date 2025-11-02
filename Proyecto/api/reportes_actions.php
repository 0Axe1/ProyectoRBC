<?php
session_start();
// Asegurarse de que el script de conexión se incluye correctamente
require_once __DIR__ . '/../config/db_connection.php';

// Verificar que el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acceso denegado. Por favor, inicie sesión.']);
    exit;
}

// Solo aceptar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

// Leer el cuerpo de la petición JSON
$input = json_decode(file_get_contents('php://input'), true);
$reportType = $input['report_type'] ?? '';
$startDate = $input['start_date'] ?? null;
$endDate = $input['end_date'] ?? null;

// --- ¡CAMBIO! Variable de $db a $pdo ---
$pdo = null; 
$data = [];
$columns = [];
$summary = null;

header('Content-Type: application/json');

try {
    // --- ¡CAMBIO! Usar la función conectarDB() que devuelve PDO ---
    $pdo = conectarDB(); 

    switch ($reportType) {
       
        // --- REPORTE DE VENTAS (MIGRADO A PDO) ---
        case 'ventas':
            if (empty($startDate) || empty($endDate)) {
                throw new Exception('Por favor, especifique una fecha de inicio y una fecha de fin.');
            }
            $columns = [
                'ID Venta' => 'id_venta',
                'Cliente' => 'nombre_razon_social',
                'Fecha' => 'fecha_venta',
                'Método de Pago' => 'nombre_metodo', // <-- CAMBIO
                'Estado del Pago' => 'nombre_estado', // <-- CAMBIO
                'Total' => 'total_venta'
            ];
           
            // CAMBIO: Consulta migrada al nuevo esquema (JOINS con pedidos, clientes, metodos_pago, estados_pago)
            $sql = "SELECT
                        v.id_venta,
                        c.nombre_razon_social,
                        v.fecha_venta,
                        mp.nombre_metodo,
                        ep.nombre_estado,
                        v.total_venta
                    FROM ventas v
                    JOIN pedidos p ON v.id_pedido = p.id_pedido
                    JOIN clientes c ON p.id_cliente = c.id_cliente
                    JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo_pago
                    JOIN estados_pago ep ON v.id_estado_pago = ep.id_estado_pago
                    WHERE DATE(v.fecha_venta) BETWEEN ? AND ?
                    ORDER BY v.fecha_venta DESC";
           
            // --- ¡CAMBIO! Sintaxis de PDO ---
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$startDate, $endDate]); // Pasar parámetros en el execute
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Usar fetchAll
            // --- FIN DEL CAMBIO ---
           
            // Calcular resumen
            $totalVentas = array_sum(array_column($data, 'total_venta'));
            $summary = "Total de Ventas en el período: Bs. " . number_format($totalVentas, 2); // 'Bs.' en lugar de '$'

            break;

        // --- REPORTE DE INVENTARIO (MIGRADO A PDO) ---
        case 'inventario':
            $columns = [
                'Producto' => 'nombre_producto',
                'Categoría' => 'nombre_categoria', // <-- CAMBIO
                'Stock' => 'cantidad_stock', // <-- CAMBIO
                'Estado' => 'estado'
            ];

            // CAMBIO: Consulta migrada (usa 'productos.cantidad_stock' y 'categorias_producto')
            $sql = "SELECT
                        p.nombre_producto,
                        c.nombre_categoria,
                        p.cantidad_stock,
                        CASE
                           WHEN p.cantidad_stock = 0 THEN 'Sin Stock'
                           WHEN p.cantidad_stock <= 50 THEN 'Bajo Stock'
                           ELSE 'En Stock'
                        END as estado
                    FROM productos p
                    JOIN categorias_producto c ON p.id_categoria = c.id_categoria
                    ORDER BY p.nombre_producto";
           
            // --- ¡CAMBIO! Sintaxis de PDO ---
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // --- FIN DEL CAMBIO ---

            $productosBajos = count(array_filter($data, fn($item) => $item['estado'] === 'Bajo Stock'));
            $summary = "Total de productos en inventario: " . count($data) . ". Productos con bajo stock: " . $productosBajos . ".";
            break;

        // --- REPORTE DE CLIENTES (MIGRADO A PDO) ---
        case 'clientes':
            $columns = [
                'Nombre / Razón Social' => 'nombre_razon_social',
                'Ubicación' => 'ubicacion',
                'Contactos' => 'contactos',
                'Total Pedidos' => 'total_pedidos'
            ];
           
            // CAMBIO: Consulta migrada (JOIN con 'tipos_contacto' y WHERE estado = 1)
            $sql = "SELECT
                        c.nombre_razon_social,
                        c.ubicacion,
                        GROUP_CONCAT(CONCAT(tc.nombre_tipo, ': ', cc.dato_contacto) SEPARATOR '; ') as contactos,
                        (SELECT COUNT(p.id_pedido) FROM pedidos p WHERE p.id_cliente = c.id_cliente) as total_pedidos
                    FROM clientes c
                    LEFT JOIN contactos_cliente cc ON c.id_cliente = cc.id_cliente
                    LEFT JOIN tipos_contacto tc ON cc.id_tipo_contacto = tc.id_tipo_contacto
                    WHERE c.estado = 1 -- Solo clientes activos
                    GROUP BY c.id_cliente, c.nombre_razon_social, c.ubicacion
                    ORDER BY c.nombre_razon_social";
           
            // --- ¡CAMBIO! Sintaxis de PDO ---
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // --- FIN DEL CAMBIO ---

            $summary = "Total de clientes activos registrados: " . count($data) . ".";
            break;

        default:
            throw new Exception('Tipo de reporte no válido.');
    }
   
    echo json_encode(['columns' => $columns, 'data' => $data, 'summary' => $summary]);

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // --- ¡CAMBIO! Cerrar la conexión PDO ---
    $pdo = null;
}
?>