<?php
header('Content-Type: application/json');
require_once '../config/db_connection.php';

try {
    $pdo = conectarDB();

    // Obtener mes y año de los parámetros GET, o usar los actuales por defecto
    $month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
    $year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

    // Validar entradas básicas
    if ($month < 1 || $month > 12) $month = intval(date('m'));
    if ($year < 2000 || $year > 2100) $year = intval(date('Y'));

    // --- 1. KPIs ---

    // Ventas del Mes (Filtrado por mes/año seleccionado)
    $query_ventas = "SELECT SUM(total_venta) as total FROM ventas WHERE MONTH(fecha_venta) = ? AND YEAR(fecha_venta) = ?";
    $stmt_ventas = $pdo->prepare($query_ventas);
    $stmt_ventas->execute([$month, $year]);
    $ventas_periodo = $stmt_ventas->fetchColumn() ?? 0;

    // Pedidos Pendientes (Siempre es el estado actual, no depende del filtro de fecha)
    $query_pendientes = "SELECT COUNT(id_pedido) as total FROM pedidos WHERE id_estado_pedido = 1"; // 1 = Pendiente
    $stmt_pendientes = $pdo->query($query_pendientes);
    $pedidos_pendientes = $stmt_pendientes->fetchColumn() ?? 0;

    // Valor del Inventario (Siempre es el estado actual)
    $query_inventario = "SELECT SUM(stock * precio) as total FROM productos WHERE activo = 1";
    $stmt_inventario = $pdo->query($query_inventario);
    $valor_inventario = $stmt_inventario->fetchColumn() ?? 0;


    // --- 2. Chart Data (Ventas diarias del mes seleccionado) ---
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $chart_labels = [];
    $chart_values = [];

    // Pre-llenar días para asegurar que el gráfico tenga continuuidad
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $chart_labels[] = $d;
        $chart_values[$d] = 0; // Inicializar en 0
    }

    $query_chart = "SELECT DAY(fecha_venta) as dia, SUM(total_venta) as total 
                    FROM ventas 
                    WHERE MONTH(fecha_venta) = ? AND YEAR(fecha_venta) = ?
                    GROUP BY DAY(fecha_venta)";
    $stmt_chart = $pdo->prepare($query_chart);
    $stmt_chart->execute([$month, $year]);
    
    while ($row = $stmt_chart->fetch(PDO::FETCH_ASSOC)) {
        $chart_values[$row['dia']] = floatval($row['total']);
    }

    // Reindexar array para enviar solo valores en orden
    $chart_data_dataset = array_values($chart_values);


    // --- 3. Top Clientes (Filtrado por el periodo seleccionado para ser más relevante) ---
    // Si el usuario filtra por Diciembre 2023, ver los mejores clientes DE ESE MES tiene más sentido
    // que ver los mejores clientes históricos globales en este contexto.
    $query_top_clientes = "SELECT c.nombre_razon_social, SUM(v.total_venta) as total_comprado
                           FROM ventas v
                           JOIN pedidos p ON v.id_pedido = p.id_pedido
                           JOIN clientes c ON p.id_cliente = c.id_cliente
                           WHERE MONTH(v.fecha_venta) = ? AND YEAR(v.fecha_venta) = ? AND c.estado = 1
                           GROUP BY c.id_cliente, c.nombre_razon_social
                           ORDER BY total_comprado DESC LIMIT 5";
    $stmt_top_clientes = $pdo->prepare($query_top_clientes);
    $stmt_top_clientes->execute([$month, $year]);
    $top_clientes = $stmt_top_clientes->fetchAll(PDO::FETCH_ASSOC);


    // --- 4. Alertas de Stock (Siempre estado actual) ---
    $query_bajo_stock = "SELECT nombre_producto, stock
                         FROM productos
                         WHERE stock < 50 AND activo = 1 
                         ORDER BY stock ASC LIMIT 5";
    $stmt_bajo_stock = $pdo->query($query_bajo_stock);
    $bajo_stock = $stmt_bajo_stock->fetchAll(PDO::FETCH_ASSOC);


    // --- Respuesta Final ---
    echo json_encode([
        'status' => 'success',
        'kpis' => [
            'ventas_periodo' => $ventas_periodo,
            'pedidos_pendientes' => $pedidos_pendientes,
            'valor_inventario' => $valor_inventario
        ],
        'chart' => [
            'labels' => $chart_labels,
            'data' => $chart_data_dataset
        ],
        'top_clientes' => $top_clientes,
        'bajo_stock' => $bajo_stock
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
