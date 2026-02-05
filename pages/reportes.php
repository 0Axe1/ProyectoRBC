<?php
// reportes.php
// --- ¡CORREGIDO! ---
// Se ha actualizado la consulta de 'tipo=productos' para usar 'stock'
// en lugar de 'cantidad_stock'.

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

try {
    if ($cliente_id) {
        // VISTA 3: DETALLES DE UN CLIENTE (Compatible)
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
        }

    } else if ($producto_id) {
        // VISTA 3: DETALLES DE UN PRODUCTO (Compatible)
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
        }

    } else if ($tipo === 'clientes') {
        // VISTA 2: LISTADO DE CLIENTES (Compatible)
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
        // VISTA 2: LISTADO DE PRODUCTOS (¡CORREGIDO!)
        $titulo = "Listado de Productos";
        // Se usa 'stock' en lugar de 'cantidad_stock'
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
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-8 flex items-center">
            <i data-lucide="bar-chart-3" class="w-6 h-6 mr-2 text-indigo-500"></i>
            Explorador de Reportes
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <a href="index.php?page=reportes&tipo=clientes" class="report-card group bg-gradient-to-br from-green-50 to-white dark:from-green-900/20 dark:to-gray-800 border-green-100 dark:border-green-900/30">
                <div class="p-4 bg-green-100 dark:bg-green-900/40 rounded-2xl mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="users" class="w-10 h-10 text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Por Cliente</h4>
                <p class="text-sm text-center text-gray-500 dark:text-gray-400">Analiza el historial de pedidos y volumen de compra por cada cliente.</p>
                <div class="mt-4 flex items-center text-green-600 dark:text-green-400 font-medium text-sm">
                    Explorar <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                </div>
            </a>
            <a href="index.php?page=reportes&tipo=productos" class="report-card group bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/20 dark:to-gray-800 border-blue-100 dark:border-blue-900/30">
                <div class="p-4 bg-blue-100 dark:bg-blue-900/40 rounded-2xl mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="package" class="w-10 h-10 text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Por Producto</h4>
                <p class="text-sm text-center text-gray-500 dark:text-gray-400">Consulta la demanda de productos y quiénes los están adquiriendo.</p>
                <div class="mt-4 flex items-center text-blue-600 dark:text-blue-400 font-medium text-sm">
                    Explorar <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                </div>
            </a>
        </div>

    <?php elseif ($tipo && (!empty($listado) || $search)): ?>
        <!-- VISTA 2: MOSTRAR LISTADO (Clientes o Productos) -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <a href="index.php?page=reportes" class="back-button group">
                <i data-lucide="chevron-left" class="w-5 h-5 mr-1 group-hover:-translate-x-1 transition-transform"></i> Volver al Menú
            </a>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="index.php" method="GET" class="relative flex-grow">
                    <input type="hidden" name="page" value="reportes">
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
                    <input type="text" name="search" 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all dark:text-gray-100" 
                        placeholder="Buscar en el listado..." 
                        value="<?php echo htmlspecialchars($search); ?>">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                </form>
                <a href="exportar_pdf.php?<?php echo $query_string; ?>" target="_blank" 
                    class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors shadow-sm"
                    title="Exportar listado actual">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </a>
            </div>
        </div>

        <?php if (empty($listado)): ?>
            <div class="p-12 text-center bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <div class="inline-flex p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4 text-gray-400">
                    <i data-lucide="search-x" class="w-8 h-8"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-400">No se encontraron resultados para "<strong><?php echo htmlspecialchars($search); ?></strong>".</p>
                <a href="index.php?page=reportes&tipo=<?php echo $tipo; ?>" class="text-indigo-600 dark:text-indigo-400 mt-2 inline-block hover:underline">Limpiar búsqueda</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($listado as $item): 
                    $url_destino = ($tipo === 'clientes') 
                        ? "index.php?page=reportes&cliente_id=" . $item['id']
                        : "index.php?page=reportes&producto_id=" . $item['id'];
                ?>
                    <a href="<?php echo $url_destino; ?>" class="report-list-item group">
                        <div class="flex-grow">
                            <h5 class="text-gray-800 dark:text-gray-100 font-semibold group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h5>
                            <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center mt-1">
                                <?php if ($tipo === 'clientes'): ?>
                                    <i data-lucide="credit-card" class="w-3 h-3 mr-1"></i> <?php echo htmlspecialchars($item['sub']); ?>
                                <?php else: ?>
                                    <i data-lucide="box" class="w-3 h-3 mr-1"></i> Stock: <?php echo htmlspecialchars($item['sub']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 transition-all group-hover:translate-x-1"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif (!empty($detalles)): ?>
        <!-- VISTA 3: MOSTRAR DETALLES (de Cliente o Producto) -->
        <?php
            $url_volver = ($cliente_id) 
                ? "index.php?page=reportes&tipo=clientes"
                : "index.php?page=reportes&tipo=productos";
        ?>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <a href="<?php echo $url_volver; ?>" class="back-button group">
                <i data-lucide="chevron-left" class="w-5 h-5 mr-1 group-hover:-translate-x-1 transition-transform"></i> Volver al listado
            </a>
            <a href="exportar_pdf.php?<?php echo $query_string; ?>" target="_blank" class="flex items-center bg-green-600 text-white px-5 py-2.5 rounded-xl hover:bg-green-700 transition-all shadow-lg shadow-green-200 dark:shadow-none font-medium">
                <i data-lucide="file-down" class="w-5 h-5 mr-2"></i> Exportar Reporte PDF
            </a>
        </div>
        
        <form action="index.php" method="GET" class="mb-8 p-6 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
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
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all font-medium flex items-center shadow-md shadow-indigo-100 dark:shadow-none">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filtrar
                    </button>
                    <?php if ($fecha_inicio || $fecha_fin): ?>
                        <a href="index.php?page=reportes&<?php echo $cliente_id ? 'cliente_id='.$cliente_id : 'producto_id='.$producto_id; ?>" 
                           class="p-2.5 text-gray-500 hover:text-red-500 bg-gray-100 dark:bg-gray-700 rounded-xl transition-colors" title="Limpiar filtros">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="text-xs font-bold text-gray-600 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <?php if ($cliente_id): ?>
                            <th scope="col" class="px-6 py-4">ID Pedido</th>
                            <th scope="col" class="px-6 py-4">Fecha</th>
                            <th scope="col" class="px-6 py-4">Estado</th>
                            <th scope="col" class="px-6 py-4 text-right">Total Venta</th>
                        <?php else: ?>
                            <th scope="col" class="px-6 py-4">Cliente</th>
                            <th scope="col" class="px-6 py-4">Cant. Pedida</th>
                            <th scope="col" class="px-6 py-4 text-center">N° Pedidos</th>
                            <th scope="col" class="px-6 py-4">Último Pedido</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($detalles)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 bg-white dark:bg-gray-800">
                                <i data-lucide="info" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                                No se encontraron datos para este rango de fechas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalles as $row): ?>
                            <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <?php if ($cliente_id): ?>
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-gray-100">#<?php echo htmlspecialchars($row['id_pedido']); ?></td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['fecha_cotizacion']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php
                                            $badge_color = 'bg-gray-100 text-gray-700';
                                            if (strpos($row['nombre_estado'], 'Pendiente') !== false) $badge_color = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
                                            if (strpos($row['nombre_estado'], 'Entregado') !== false) $badge_color = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                                            if (strpos($row['nombre_estado'], 'Cancelado') !== false) $badge_color = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                                        ?>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-semibold <?php echo $badge_color; ?>">
                                            <?php echo htmlspecialchars($row['nombre_estado']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-gray-100">
                                        <?php echo $row['total_venta'] ? 'Bs. ' . number_format($row['total_venta'], 2) : '---'; ?>
                                    </td>
                                <?php else: ?>
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($row['nombre_razon_social']); ?></td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400"><span class="font-bold text-indigo-600 dark:text-indigo-400"><?php echo htmlspecialchars($row['total_cantidad_pedida']); ?></span> unidades</td>
                                    <td class="px-6 py-4 text-center text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($row['numero_de_pedidos']); ?></td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-500 text-xs italic"><?php echo htmlspecialchars($row['ultima_fecha_pedido']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="p-12 text-center bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
            <a href="index.php?page=reportes" class="back-button mb-6 inline-flex">
                <i data-lucide="chevron-left" class="w-5 h-5 mr-1"></i> Volver
            </a>
            <p class="text-gray-500 dark:text-gray-400">No se encontraron detalles o no hay datos para mostrar.</p>
        </div>
    <?php endif; ?>

</div>

<style>
.report-card { 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    justify-content: center; 
    padding: 3rem 2rem; 
    border-width: 2px;
    border-style: solid;
    border-radius: 1.5rem; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
    cursor: pointer;
    text-align: center;
}
.report-card:hover { 
    transform: translateY(-8px); 
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); 
    background-color: white;
}
.dark .report-card:hover {
    background-color: rgba(31, 41, 55, 0.8);
}

.back-button { 
    display: inline-flex; 
    align-items: center; 
    padding: 0.625rem 1.25rem; 
    font-size: 0.875rem; 
    font-weight: 600; 
    color: #ffffffff; 
    background-color: #374151; 
    border-radius: 0.75rem; 
    transition: all 0.2s ease; 
}
.back-button:hover { 
    background-color: #e5e7eb; 
    color: #111827;
}
.dark .back-button { 
    color: #d1d5db; 
    background-color: #374151; 
}
.dark .back-button:hover { 
    background-color: #4b5563; 
    color: #f9fafb;
}

.report-list-item {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background-color: #374151;
    border: 1px solid #2c333fff;
    border-radius: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}
.dark .report-list-item {
    background-color: #1f2937;
    border-color: #374151;
}
.report-list-item:hover {
    transform: translateX(4px);
    border-color: #818cf8;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}
</style>