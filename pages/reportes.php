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

<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">

    <?php if (!$tipo && !$cliente_id && !$producto_id): ?>
        <!-- VISTA 1: SELECCIÓN PRINCIPAL (Default) -->
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Explorador de Reportes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="index.php?page=reportes&tipo=clientes" class="report-card group">
                <i data-lucide="users" class="w-12 h-12 text-green-600 dark:text-green-400 mb-4 transition-transform group-hover:scale-110"></i>
                <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Por Cliente</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Ver listado de clientes y sus pedidos.</p>
            </a>
            <a href="index.php?page=reportes&tipo=productos" class="report-card group">
                <i data-lucide="package" class="w-12 h-12 text-blue-600 dark:text-blue-400 mb-4 transition-transform group-hover:scale-110"></i>
                <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Por Producto</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Ver listado de productos y quién los pidió.</p>
            </a>
        </div>

    <?php elseif ($tipo && !empty($listado)): ?>
        <!-- VISTA 2: MOSTRAR LISTADO (Clientes o Productos) -->
        <div class="flex justify-between items-center mb-4">
            <a href="index.php?page=reportes" class="back-button">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver
            </a>
            <a href="exportar_pdf.php?<?php echo $query_string; ?>" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm inline-flex items-center transition-transform duration-300 hover:scale-105">
                <i data-lucide="file-down" class="w-4 h-4 mr-2"></i> Exportar PDF
            </a>
        </div>
        
        <form action="index.php" method="GET" class="relative mt-4 mb-4">
            <input type="hidden" name="page" value="reportes">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="Filtrar listado..." value="<?php echo htmlspecialchars($search); ?>">
            <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:text-blue-600">
                <i data-lucide="search" class="w-5 h-5"></i>
            </button>
        </form>

        <div class="report-list-container">
            <?php foreach ($listado as $item): ?>
                <?php
                    $url_destino = ($tipo === 'clientes') 
                        ? "index.php?page=reportes&cliente_id=" . $item['id']
                        : "index.php?page=reportes&producto_id=" . $item['id'];
                ?>
                <a href="<?php echo $url_destino; ?>" class="list-item block">
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-sub">
                        <!-- ¡CORREGIDO! Muestra 'Stock: ' para productos -->
                        <?php echo ($tipo === 'clientes') ? htmlspecialchars($item['sub']) : 'Stock: ' . htmlspecialchars($item['sub']); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

    <?php elseif ($tipo && empty($listado)): ?>
        <!-- VISTA 2: NO HAY RESULTADOS DE BÚSQUEDA -->
        <a href="index.php?page=reportes" class="back-button mb-4">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver
        </a>
        <form action="index.php" method="GET" class="relative mt-4 mb-4">
            <input type="hidden" name="page" value="reportes">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="Filtrar listado..." value="<?php echo htmlspecialchars($search); ?>">
            <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-500 hover:text-blue-600">
                <i data-lucide="search" class="w-5 h-5"></i>
            </button>
        </form>
        <div class="p-4 text-center text-gray-500">No se encontraron resultados para "<?php echo htmlspecialchars($search); ?>".</div>

    <?php elseif (!empty($detalles)): ?>
        <!-- VISTA 3: MOSTRAR DETALLES (de Cliente o Producto) -->
        <?php
            $url_volver = ($cliente_id) 
                ? "index.php?page=reportes&tipo=clientes"
                : "index.php?page=reportes&tipo=productos";
        ?>

        <div class="flex justify-between items-center mb-4">
            <a href="<?php echo $url_volver; ?>" class="back-button">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver al listado
            </a>
            <a href="exportar_pdf.php?<?php echo $query_string; ?>" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm inline-flex items-center transition-transform duration-300 hover:scale-105">
                <i data-lucide="file-down" class="w-4 h-4 mr-2"></i> Exportar PDF
            </a>
        </div>
        
        <form action="index.php" method="GET" class="my-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
            <input type="hidden" name="page" value="reportes">
            <?php if ($cliente_id): ?>
                <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
            <?php else: ?>
                <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
            <?php endif; ?>
            
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-grow">
                    <label for="date-start-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" id="date-start-filter" class="w-full p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 text-sm" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                <div class="flex-grow">
                    <label for="date-end-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Fin</label>
                    <input type="date" name="fecha_fin" id="date-end-filter" class="w-full p-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 text-sm" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
                <div class="flex-shrink-0 pt-5">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm inline-flex items-center">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filtrar
                    </button>
                    <a href="index.php?page=reportes&<?php echo $cliente_id ? 'cliente_id='.$cliente_id : 'producto_id='.$producto_id; ?>" title="Limpiar filtros" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto border dark:border-gray-700 rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <?php if ($cliente_id): ?>
                            <th scope="col" class="px-6 py-3">ID Pedido</th>
                            <th scope="col" class="px-6 py-3">Fecha</th>
                            <th scope="col" class="px-6 py-3">Estado</th>
                            <th scope="col" class="px-6 py-3">Total Venta</th>
                        <?php else: ?>
                            <th scope="col" class="px-6 py-3">Cliente</th>
                            <th scope="col" class="px-6 py-3">Cantidad Total Pedida</th>
                            <th scope="col" class="px-6 py-3">N° de Pedidos</th>
                            <th scope="col" class="px-6 py-3">Último Pedido</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalles)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center">No se encontraron datos para este rango de fechas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalles as $row): ?>
                            <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <?php if ($cliente_id): ?>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['id_pedido']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['fecha_cotizacion']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['nombre_estado']); ?></td>
                                    <td class="px-6 py-4"><?php echo $row['total_venta'] ? 'Bs. ' . number_format($row['total_venta'], 2) : 'N/A'; ?></td>
                                <?php else: ?>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['nombre_razon_social']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['total_cantidad_pedida']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['numero_de_pedidos']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['ultima_fecha_pedido']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <a href="index.php?page=reportes" class="back-button mb-4">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Volver
        </a>
        <div class="p-4 text-center text-gray-500">No se encontraron detalles o no hay datos para mostrar.</div>
    <?php endif; ?>

</div>

<style>
.report-card { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; border: 1px solid #e2e8f0; border-radius: 0.75rem; background-color: #f8fafc; transition: all 0.3s ease; }
.report-card:hover { transform: translateY(-5px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-color: #22c55e; }
.dark .report-card { border-color: #374151; background-color: #1f2937; }
.dark .report-card:hover { border-color: #22c55e; box-shadow: 0 0 15px rgba(34, 197, 94, 0.1); }
.back-button { display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; color: #4b5563; background-color: #f3f4f6; border-radius: 0.5rem; transition: background-color 0.2s ease; }
.back-button:hover { background-color: #e5e7eb; }
.dark .back-button { color: #d1d5db; background-color: #374151; }
.dark .back-button:hover { background-color: #4b5563; }
.report-list-container { max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
.dark .report-list-container { border-color: #374151; }
.list-item { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background-color 0.2s ease; }
.list-item:last-child { border-bottom: 0; }
.list-item:hover { background-color: #f9fafb; }
.dark .list-item { border-color: #374151; }
.dark .list-item:hover { background-color: #374151; }
.list-item .item-name { font-weight: 500; color: #111827; }
.list-item .item-sub { font-size: 0.875rem; color: #6b7280; }
.dark .list-item .item-name { color: #f9fafb; }
.dark .list-item .item-sub { color: #9ca3af; }
</style>