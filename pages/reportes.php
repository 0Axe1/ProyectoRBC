<?php
// reportes.php

// Obtenemos los parámetros de la URL
$tipo = $_GET['tipo'] ?? null;
$cliente_id = filter_var($_GET['cliente_id'] ?? null, FILTER_VALIDATE_INT);
$producto_id = filter_var($_GET['producto_id'] ?? null, FILTER_VALIDATE_INT);

$search = trim($_GET['search'] ?? '');
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$query_string = http_build_query($_GET);

$listado = [];
$detalles = [];
$titulo = "Explorador de Reportes";
$params = [];

// Variables para tarjetas de resumen
$total_ventas_periodo = 0;
$total_pedidos_periodo = 0;
$total_unidades_periodo = 0;

try {
    if ($cliente_id) {
        // VISTA 3: DETALLES DE UN CLIENTE
        $titulo = "Detalle de Cliente";
        
        $sql = "SELECT p.id_pedido, DATE_FORMAT(p.fecha_cotizacion, '%d/%m/%Y') as fecha_cotizacion, 
                        ep.nombre_estado, v.total_venta, c.nombre_razon_social
                FROM pedidos p
                JOIN estados_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
                JOIN clientes c ON p.id_cliente = c.id_cliente
                LEFT JOIN ventas v ON p.id_pedido = v.id_pedido
                WHERE p.id_cliente = ?";
        $params[] = $cliente_id;

        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND p.fecha_cotizacion BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        $sql .= " ORDER BY p.fecha_cotizacion DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($detalles)) {
            $titulo = "Pedidos de: " . htmlspecialchars($detalles[0]['nombre_razon_social']);
            
            // Calcular totales para tarjetas
            $total_pedidos_periodo = count($detalles);
            foreach ($detalles as $d) {
                $total_ventas_periodo += $d['total_venta'] ?? 0;
            }
        }

    } else if ($producto_id) {
        // VISTA 3: DETALLES DE UN PRODUCTO
        $titulo = "Detalle de Producto";
        
        $sql = "SELECT c.nombre_razon_social, SUM(ddp.cantidad_pedido) as total_cantidad_pedida,
                        COUNT(DISTINCT p.id_pedido) as numero_de_pedidos,
                        MAX(DATE_FORMAT(p.fecha_cotizacion, '%d/%m/%Y')) as ultima_fecha_pedido,
                        pr.nombre_producto
                FROM detalle_de_pedido ddp
                JOIN pedidos p ON ddp.id_pedido = p.id_pedido
                JOIN clientes c ON p.id_cliente = c.id_cliente
                JOIN productos pr ON ddp.id_producto = pr.id_producto
                WHERE ddp.id_producto = ? AND c.estado = 1";
        $params[] = $producto_id;

        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND p.fecha_cotizacion BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        $sql .= " GROUP BY c.id_cliente, c.nombre_razon_social
                  ORDER BY total_cantidad_pedida DESC, c.nombre_razon_social";
                  
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($detalles)) {
            $titulo = "Pedidos del Producto: " . htmlspecialchars($detalles[0]['nombre_producto']);
            
             // Calcular totales para tarjetas
            foreach ($detalles as $d) {
                $total_unidades_periodo += $d['total_cantidad_pedida'];
                $total_pedidos_periodo += $d['numero_de_pedidos'];
            }
        }

    } else if ($tipo === 'clientes') {
        // VISTA 2: LISTADO DE CLIENTES
        $titulo = "Listado de Clientes";
        $sql = "SELECT id_cliente as id, nombre_razon_social as name, nit_ruc as sub
                FROM clientes WHERE estado = 1";
        if ($search) {
            $sql .= " AND (nombre_razon_social LIKE ? OR nit_ruc LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY nombre_razon_social";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $listado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else if ($tipo === 'productos') {
        // VISTA 2: LISTADO DE PRODUCTOS
        $titulo = "Listado de Productos";
        $sql = "SELECT id_producto as id, nombre_producto as name, stock as sub
                FROM productos WHERE activo = 1";
        if ($search) {
            $sql .= " AND (nombre_producto LIKE ?)";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY nombre_producto";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $listado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    echo '<div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">Error de base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Actualizar el título principal de la página
echo "<script>document.getElementById('page-title').textContent = '" . addslashes($titulo) . "';</script>";

?>

<div class="modulo-reportes bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">

    <?php if (!$tipo && !$cliente_id && !$producto_id): ?>
        <!-- VISTA 1: SELECCIÓN PRINCIPAL (Default) -->
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-8 flex items-center border-b pb-4 border-gray-100 dark:border-gray-700">
            <i data-lucide="pie-chart" class="w-6 h-6 mr-2 text-indigo-500"></i>
            Panel de Reportes
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Opción Clientes -->
            <a href="index.php?page=reportes&tipo=clientes" class="group relative flex flex-col items-center justify-center p-8 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-3xl hover:border-indigo-100 dark:hover:border-indigo-900 hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent dark:from-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                
                <div class="relative z-10 p-5 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl mb-6 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="users" class="w-12 h-12"></i>
                </div>
                
                <h4 class="relative z-10 text-2xl font-bold text-gray-800 dark:text-gray-100 mb-3 text-center">
                    Reporte por Cliente
                </h4>
                
                <p class="relative z-10 text-gray-500 dark:text-gray-400 text-center max-w-xs mb-6 leading-relaxed">
                    Analiza el historial de pedidos, estados y volumen de compra detallado por cada cliente.
                </p>
                
                <div class="relative z-10 flex items-center text-indigo-600 dark:text-indigo-400 font-semibold group-hover:translate-x-1 transition-transform">
                    Consultar Clientes <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                </div>
            </a>

            <!-- Opción Productos -->
            <a href="index.php?page=reportes&tipo=productos" class="group relative flex flex-col items-center justify-center p-8 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-3xl hover:border-emerald-100 dark:hover:border-emerald-900 hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent dark:from-emerald-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                
                <div class="relative z-10 p-5 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl mb-6 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="package" class="w-12 h-12"></i>
                </div>
                
                <h4 class="relative z-10 text-2xl font-bold text-gray-800 dark:text-gray-100 mb-3 text-center">
                    Reporte por Producto
                </h4>
                
                <p class="relative z-10 text-gray-500 dark:text-gray-400 text-center max-w-xs mb-6 leading-relaxed">
                     Visualiza la demanda de productos, rotación de inventario y quiénes los están comprando.
                </p>
                
                <div class="relative z-10 flex items-center text-emerald-600 dark:text-emerald-400 font-semibold group-hover:translate-x-1 transition-transform">
                    Consultar Productos <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                </div>
            </a>
        </div>

    <?php elseif ($tipo && (!empty($listado) || $search)): ?>
        <!-- VISTA 2: MOSTRAR LISTADO (Clientes o Productos) -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <a href="index.php?page=reportes" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver al Panel
            </a>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="index.php" method="GET" class="relative flex-grow sm:flex-grow-0 sm:w-80">
                    <input type="hidden" name="page" value="reportes">
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
                    <input type="text" name="search" 
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all dark:text-gray-100 text-sm" 
                        placeholder="Buscar <?php echo $tipo === 'clientes' ? 'cliente...' : 'producto...'; ?>" 
                        value="<?php echo htmlspecialchars($search); ?>">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                </form>
                
                <button type="submit" form="search-form" class="hidden sm:inline-flex"></button> 

                <a href="exportar_pdf.php?<?php echo $query_string; ?>" target="_blank" 
                    class="p-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:text-red-600 hover:border-red-200 dark:hover:text-red-400 transition-all shadow-sm"
                    title="Exportar listado PDF">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </a>
            </div>
        </div>

        <?php if (empty($listado)): ?>
            <div class="flex flex-col items-center justify-center p-12 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                    <i data-lucide="search-x" class="w-8 h-8 text-gray-400"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-400 font-medium mb-2">No se encontraron resultados</p>
                <p class="text-sm text-gray-500 mb-4">No hay coincidencias para "<strong><?php echo htmlspecialchars($search); ?></strong>".</p>
                <a href="index.php?page=reportes&tipo=<?php echo $tipo; ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm font-medium">Limpiar filtros de búsqueda</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($listado as $item): 
                    $url_destino = ($tipo === 'clientes') 
                        ? "index.php?page=reportes&cliente_id=" . $item['id']
                        : "index.php?page=reportes&producto_id=" . $item['id'];
                    
                    $icon = ($tipo === 'clientes') ? 'user' : 'box';
                    $sub_label = ($tipo === 'clientes') ? 'NIT/RUC: ' : 'Stock Disponible: ';
                    $sub_class = ($tipo === 'clientes') ? 'text-gray-500' : 'text-emerald-600 dark:text-emerald-400 font-medium';
                ?>
                    <a href="<?php echo $url_destino; ?>" class="group block p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 rounded-xl shadow-sm hover:shadow-md transition-all">
                        <div class="flex justify-between items-start">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 transition-colors">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors mb-1 line-clamp-1">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h5>
                                    <p class="text-xs <?php echo $sub_class; ?> flex items-center">
                                        <?php echo $sub_label . htmlspecialchars($item['sub']); ?>
                                    </p>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition-all"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif (!empty($detalles) || $fecha_inicio || $fecha_fin): ?>
        <!-- VISTA 3: MOSTRAR DETALLES (de Cliente o Producto) -->
        <?php
            $url_volver = ($cliente_id) 
                ? "index.php?page=reportes&tipo=clientes"
                : "index.php?page=reportes&tipo=productos";
        ?>

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <a href="<?php echo $url_volver; ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver
                </a>
                <div>
                     <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <?php echo ($cliente_id) ? 'Historial de Pedidos' : 'Análisis de Producto'; ?>
                     </h2>
                     <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?php echo ($cliente_id) ? 'Cliente: ' : 'Producto: '; ?>
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            <?php echo $cliente_id ? htmlspecialchars($detalles[0]['nombre_razon_social'] ?? 'Desconocido') : htmlspecialchars($detalles[0]['nombre_producto'] ?? 'Desconocido'); ?>
                        </span>
                     </p>
                </div>
            </div>
            
            <a href="exportar_pdf.php?<?php echo $query_string; ?>" target="_blank" class="inline-flex items-center px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 focus:ring-4 focus:ring-red-200 dark:focus:ring-red-900 transition-all shadow-md shadow-red-200 dark:shadow-none">
                <i data-lucide="file-down" class="w-5 h-5 mr-2"></i> Reporte PDF
            </a>
        </div>
        
        <!-- Tarjetas de Resumen (NUEVO) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center">
                <div class="p-3 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 mr-4">
                    <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Pedidos</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo number_format($total_pedidos_periodo); ?></p>
                </div>
            </div>

            <?php if ($cliente_id): ?>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center">
                <div class="p-3 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 mr-4">
                    <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total Compras</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">Bs. <?php echo number_format($total_ventas_periodo, 2); ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center">
                <div class="p-3 rounded-full bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 mr-4">
                    <i data-lucide="layers" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Unidades Vendidas</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo number_format($total_unidades_periodo); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Filtros -->
        <form action="index.php" method="GET" class="mb-8 p-6 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100 dark:border-gray-700">
            <input type="hidden" name="page" value="reportes">
            <?php if ($cliente_id): ?>
                <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
            <?php else: ?>
                <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
            <?php endif; ?>
            
            <div class="flex flex-wrap items-end gap-6">
                <div class="flex-grow min-w-[200px]">
                    <label for="date-start-filter" class="block text-xs font-bold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-2 ml-1">Fecha Desde</label>
                    <div class="relative">
                        <input type="date" name="fecha_inicio" id="date-start-filter" 
                            class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all dark:text-gray-100 text-sm" 
                            value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    </div>
                </div>
                <div class="flex-grow min-w-[200px]">
                    <label for="date-end-filter" class="block text-xs font-bold text-gray-500 uppercase tracking-wider dark:text-gray-400 mb-2 ml-1">Fecha Hasta</label>
                    <div class="relative">
                        <input type="date" name="fecha_fin" id="date-end-filter" 
                            class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all dark:text-gray-100 text-sm" 
                            value="<?php echo htmlspecialchars($fecha_fin); ?>">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="flex-1 sm:flex-none px-6 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all font-medium flex items-center justify-center shadow-md shadow-indigo-100 dark:shadow-none">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filtrar
                    </button>
                    <?php if ($fecha_inicio || $fecha_fin): ?>
                        <a href="index.php?page=reportes&<?php echo $cliente_id ? 'cliente_id='.$cliente_id : 'producto_id='.$producto_id; ?>" 
                           class="px-4 py-2.5 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 bg-white dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 transition-colors" title="Limpiar filtros">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Tabla de Datos -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <?php if ($cliente_id): ?>
                                <th scope="col" class="px-6 py-4">ID Pedido</th>
                                <th scope="col" class="px-6 py-4">Fecha</th>
                                <th scope="col" class="px-6 py-4">Estado</th>
                                <th scope="col" class="px-6 py-4 text-right">Total (Bs)</th>
                            <?php else: ?>
                                <th scope="col" class="px-6 py-4">Cliente</th>
                                <th scope="col" class="px-6 py-4 text-center">Cant. Pedida</th>
                                <th scope="col" class="px-6 py-4 text-center">N° Pedidos</th>
                                <th scope="col" class="px-6 py-4 text-center">Último Pedido</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($detalles)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="calendar-off" class="w-10 h-10 mb-2 text-gray-300 dark:text-gray-600"></i>
                                        <span>No hay datos registrados en este periodo.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detalles as $row): ?>
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <?php if ($cliente_id): ?>
                                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                            #<?php echo htmlspecialchars($row['id_pedido']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            <?php echo htmlspecialchars($row['fecha_cotizacion']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                                $estado_lower = strtolower($row['nombre_estado']);
                                                $badge_color = 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                                
                                                if (strpos($estado_lower, 'pendiente') !== false) 
                                                    $badge_color = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800';
                                                elseif (strpos($estado_lower, 'entregado') !== false || strpos($estado_lower, 'completado') !== false) 
                                                    $badge_color = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
                                                elseif (strpos($estado_lower, 'cancelado') !== false) 
                                                    $badge_color = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800';
                                                elseif (strpos($estado_lower, 'proceso') !== false) 
                                                    $badge_color = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge_color; ?>">
                                                <?php echo htmlspecialchars($row['nombre_estado']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                            <?php echo $row['total_venta'] ? number_format($row['total_venta'], 2) : '---'; ?>
                                        </td>
                                    <?php else: ?>
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100">
                                            <?php echo htmlspecialchars($row['nombre_razon_social']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
                                                <?php echo htmlspecialchars($row['total_cantidad_pedida']); ?> u.
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-400">
                                            <?php echo htmlspecialchars($row['numero_de_pedidos']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-500 dark:text-gray-500 text-xs italic">
                                            <?php echo htmlspecialchars($row['ultima_fecha_pedido']); ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <div class="flex flex-col items-center justify-center p-16 text-center">
            <div class="bg-gray-100 dark:bg-gray-700/50 p-6 rounded-full mb-6 animate-pulse">
                <i data-lucide="bar-chart-2" class="w-16 h-16 text-gray-300 dark:text-gray-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Selecciona un tipo de reporte</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-8">
                Elige una de las opciones disponibles arriba para comenzar a explorar los datos de tu negocio.
            </p>
            <a href="index.php?page=reportes&tipo=clientes" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                Ir a Reportes por Cliente &rarr;
            </a>
        </div>
    <?php endif; ?>

</div>