<?php
// ... (toda la lógica PHP de consulta de KPIs) ...
// -- Ventas del Mes Actual (Compatible) --
$query_ventas_mes = "SELECT SUM(total_venta) as total FROM ventas WHERE MONTH(fecha_venta) = MONTH(CURDATE()) AND YEAR(fecha_venta) = YEAR(CURDATE())";
$result_ventas_mes = $pdo->query($query_ventas_mes);
$ventas_mes = $result_ventas_mes->fetchColumn() ?? 0; // fetchColumn() es más directo para un solo valor

// -- Pedidos Pendientes (MIGRADO) --
$query_pedidos_pendientes = "SELECT COUNT(id_pedido) as total FROM pedidos WHERE id_estado_pedido = 1";
$result_pedidos_pendientes = $pdo->query($query_pedidos_pendientes);
$pedidos_pendientes = $result_pedidos_pendientes->fetchColumn() ?? 0;

// -- Valor Total del Inventario (MIGRADO) --
$query_valor_inventario = "SELECT SUM(cantidad_stock * precio_venta_sugerido) as total FROM productos";
$result_valor_inventario = $pdo->query($query_valor_inventario);
$valor_inventario = $result_valor_inventario->fetchColumn() ?? 0;

// --- 2. DATOS PARA GRÁFICOS Y LISTAS ---

// -- Gráfico de Ventas Mensuales (Últimos 6 meses) --
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
   
    // Ventas del mes
    $query_ventas = $pdo->prepare("SELECT SUM(total_venta) as total FROM ventas WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ?");
    $query_ventas->execute([$mes]);
    $total_ventas_mes = $query_ventas->fetchColumn() ?? 0;
   
    $chart_data['labels'][] = date('M Y', strtotime("-$i months"));
    $chart_data['ventas'][] = $total_ventas_mes;
}

// -- Alertas de Bajo Stock (MIGRADO) --
$query_bajo_stock = "SELECT nombre_producto, cantidad_stock
                     FROM productos
                     WHERE cantidad_stock < 50 ORDER BY cantidad_stock ASC LIMIT 5";
$productos_bajo_stock = $pdo->query($query_bajo_stock); // $productos_bajo_stock es ahora un PDOStatement

// -- Top 5 Clientes (MIGRADO) --
$query_top_clientes = "SELECT c.nombre_razon_social, SUM(v.total_venta) as total_comprado
                       FROM ventas v
                       JOIN pedidos p ON v.id_pedido = p.id_pedido
                       JOIN clientes c ON p.id_cliente = c.id_cliente
                       WHERE c.estado = 1 -- Solo clientes activos
                       GROUP BY c.id_cliente, c.nombre_razon_social
                       ORDER BY total_comprado DESC LIMIT 5";
$top_clientes = $pdo->query($query_top_clientes); // $top_clientes es ahora un PDOStatement

?>
<!-- Incluir Chart.js desde un CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ¡NUEVO! Inyectar los datos de PHP en una variable JS global para que el script externo los lea -->
<script>
    const chartData = <?php echo json_encode($chart_data); ?>;
</script>

<div class="fade-in">
    <!-- 1. Encabezado Principal (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Card: Ventas del Mes -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-full">
                    <i data-lucide="trending-up" class="w-7 h-7 text-green-500 dark:text-green-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ventas del Mes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">Bs. <?php echo number_format($ventas_mes, 2); ?></p>
                </div>
            </div>
        </div>
        <!-- Card: Pedidos Pendientes -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-full">
                    <i data-lucide="clock" class="w-7 h-7 text-yellow-500 dark:text-yellow-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pedidos Pendientes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo e($pedidos_pendientes); ?></p>
                </div>
            </div>
        </div>
        <!-- Card: Valor del Inventario -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg transform hover:scale-105 transition-transform duration-300">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                    <i data-lucide="archive" class="w-7 h-7 text-blue-500 dark:text-blue-400"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor Inventario (Est.)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">Bs. <?php echo number_format($valor_inventario, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Gráficos y Secciones de Alertas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Columna Principal (Gráfico) -->
        <div class="lg:col-span-2 p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Ventas (Últimos 6 Meses)</h3>
            <div>
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Columna Secundaria (Alertas y Listas) -->
        <div class="space-y-8">
            <!-- Sección de Alertas -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mr-2 text-red-500"></i> Alertas de Inventario
                </h3>
                <div class="space-y-3">
                    <!-- ¡MODIFICADO! Bucle PDO -->
                    <?php if ($productos_bajo_stock->rowCount() > 0): ?>
                        <?php while($producto = $productos_bajo_stock->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="flex justify-between items-center text-sm p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <p class="text-gray-700 dark:text-gray-300"><?php echo e($producto['nombre_producto']); ?></p>
                                <span class="font-bold text-red-500"><?php echo e($producto['cantidad_stock']); ?> Unid.</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay alertas de bajo stock.</p>
                    <?php endif; ?>
                </div>
            </div>
           
            <!-- Panel de Top Clientes -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg">
                 <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
                    <i data-lucide="award" class="w-5 h-5 mr-2 text-amber-500"></i> Top 5 Clientes
                 </h3>
                 <div class="space-y-3">
                    <!-- ¡MODIFICADO! Bucle PDO -->
                    <?php if ($top_clientes->rowCount() > 0): ?>
                        <?php while($cliente = $top_clientes->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="flex justify-between items-center text-sm p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <p class="font-medium text-gray-800 dark:text-gray-200 truncate pr-4"><?php echo e($cliente['nombre_razon_social']); ?></p>
                                <span class="font-semibold text-gray-600 dark:text-gray-300">Bs. <?php echo number_format($cliente['total_comprado'], 2); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay datos de ventas para mostrar.</p>
                    <?php endif; ?>
                 </div>
            </div>
        </div>
    </div>
</div>

<!-- ¡CAMBIO! SCRIPT ELIMINADO -->
