<?php
/**
 * Módulo de Gestión de Pedidos con Paginación del lado del servidor
 */

// --- 1. Configuración de Paginación y Búsqueda ---
$limit = 10; // Registros por página
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $limit;

$search_term = trim($_GET['search'] ?? '');
$params = [];

// --- 2. Consulta para obtener el total de registros (Paginación) ---
// Necesitamos saber cuántos hay en total para calcular las páginas totales
$sql_count = "SELECT COUNT(*) FROM pedidos ped JOIN clientes cli ON ped.id_cliente = cli.id_cliente";
if (!empty($search_term)) {
    $sql_count .= " WHERE (cli.nombre_razon_social LIKE ? OR ped.id_pedido LIKE ?)";
    $count_params = ['%' . $search_term . '%', '%' . $search_term . '%'];
} else {
    $count_params = [];
}
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($count_params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Ajuste de seguridad: Si el usuario pide una página mayor a la existente
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

// --- 3. Consulta Principal con LIMIT y OFFSET ---
$sql = "SELECT
            ped.id_pedido,
            cli.nombre_razon_social,
            ped.id_cliente,
            ped.direccion_entrega,
            ped.fecha_cotizacion,
            DATE_FORMAT(ped.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_fmt,
            DATE_FORMAT(ped.fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion_fmt,
            est.nombre_estado AS estado,
            (SELECT SUM(precio_total_cotizado)
             FROM detalle_de_pedido
             WHERE id_pedido = ped.id_pedido) as total_pedido
        FROM pedidos ped
        JOIN clientes cli ON ped.id_cliente = cli.id_cliente
        JOIN estados_pedido est ON ped.id_estado_pedido = est.id_estado_pedido";

if (!empty($search_term)) {
    $sql .= " WHERE (cli.nombre_razon_social LIKE ? OR ped.id_pedido LIKE ?)";
    $search_like = '%' . $search_term . '%';
    $params[] = $search_like;
    $params[] = $search_like;
}

$sql .= " ORDER BY ped.fecha_cotizacion DESC, ped.id_pedido DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Función auxiliar para mantener parámetros de búsqueda en los links de paginación
function pagination_url($page_num, $search) {
    $url = "?page=pedidos&p=" . $page_num;
    if (!empty($search)) {
        $url .= "&search=" . urlencode($search);
    }
    return $url;
}
?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">
    <div id="message-container"></div>

    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Gestión de Pedidos</h3>
        <button id="add-order-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Nuevo Pedido
        </button>
    </div>

    <div class="mb-4">
        <form action="index.php" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="page" value="pedidos">
            <div class="relative flex-grow w-full sm:w-auto">
                <label for="search" class="sr-only">Buscar pedido</label>
                <input type="text" id="search" name="search"
                       class="w-full px-4 py-2 pl-10 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Buscar por Cliente o ID de Pedido..."
                       value="<?php echo e($search_term); ?>">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </span>
            </div>
            <button type="submit" class="w-full sm:w-auto px-4 py-2 flex items-center justify-center bg-green-600 text-white rounded-lg hover:bg-green-700 transition-transform duration-300 hover:scale-105">
                Buscar
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php?page=pedidos" class="w-full sm:w-auto px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b dark:border-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">ID</th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <i data-lucide="user" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Cliente
                    </th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Fecha Cotiz.
                    </th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <i data-lucide="dollar-sign" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Total
                    </th>
                    <th scope="col" class="px-6 py-4 font-semibold">Estado</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Creado</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Actualizado</th>
                    <th scope="col" class="px-6 py-4 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stmt && $stmt->rowCount() > 0) : ?>
                    <?php while($pedido = $stmt->fetch(PDO::FETCH_ASSOC)):
                        $estado_classes = [
                            'Pendiente' => 'bg-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'Entregado' => 'bg-green-200 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'Cancelado' => 'bg-red-200 text-red-800 dark:bg-red-900 dark:text-red-300',
                            'En Preparacion' => 'bg-blue-200 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        ];
                        $estado_class = $estado_classes[$pedido['estado']] ?? 'bg-gray-200 text-gray-800';
                    ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-bold">#<?php echo e($pedido['id_pedido']); ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($pedido['nombre_razon_social']); ?></td>
                            <td class="px-6 py-4"><?php echo date("d/m/Y", strtotime($pedido['fecha_cotizacion'])); ?></td>
                            <td class="px-6 py-4 font-medium">$ <?php echo number_format($pedido['total_pedido'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo e($estado_class); ?>"><?php echo e($pedido['estado']); ?></span></td>
                            <td class="px-6 py-4 text-xs"><?php echo e($pedido['fecha_creacion_fmt']); ?></td>
                            <td class="px-6 py-4 text-xs"><?php echo e($pedido['fecha_actualizacion_fmt']); ?></td>

                            <td class="px-6 py-4 text-right flex justify-end space-x-2">
                                <?php if ($pedido['estado'] === 'Pendiente'): ?>
                                    <!-- Botón Editar (Solo para Pendiente) -->
                                    <button class="edit-order-btn flex items-center text-sm font-medium text-blue-600 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 px-3 py-1 rounded-lg"
                                            data-id="<?php echo e($pedido['id_pedido']); ?>"
                                            data-cliente-id="<?php echo e($pedido['id_cliente']); ?>"
                                            data-cliente-nombre="<?php echo e(e($pedido['nombre_razon_social'])); ?>"
                                            data-fecha="<?php echo e($pedido['fecha_cotizacion']); ?>"
                                            data-direccion="<?php echo e(e($pedido['direccion_entrega'])); ?>"
                                            data-estado="<?php echo e(e($pedido['estado'])); ?>">
                                        <i data-lucide="pencil" class="w-4 h-4 mr-1"></i> Editar
                                    </button>

                                    <!-- Botones de Acción (Solo para Pendiente) -->
                                    <form action="api/pedidos_actions.php" method="POST" class="inline deliver-form ml-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="deliver">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="flex items-center text-sm font-medium text-green-600 bg-green-100 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 px-3 py-1 rounded-lg">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i> Entregar
                                        </button>
                                    </form>
                                   
                                    <form action="api/pedidos_actions.php" method="POST" class="inline cancel-form ml-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="flex items-center text-sm font-medium text-red-600 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 px-3 py-1 rounded-lg">
                                            <i data-lucide="x-circle" class="w-4 h-4 mr-1"></i> Cancelar
                                        </button>
                                    </form>

                                <?php elseif ($pedido['estado'] === 'En Preparacion'): ?>
                                     <!-- En Preparación: Ver + Acciones -->
                                    <button class="view-order-btn flex items-center text-sm font-medium text-blue-600 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 px-3 py-1 rounded-lg"
                                            data-id="<?php echo e($pedido['id_pedido']); ?>"
                                            data-cliente-id="<?php echo e($pedido['id_cliente']); ?>"
                                            data-cliente-nombre="<?php echo e(e($pedido['nombre_razon_social'])); ?>"
                                            data-fecha="<?php echo e($pedido['fecha_cotizacion']); ?>"
                                            data-direccion="<?php echo e(e($pedido['direccion_entrega'])); ?>"
                                            data-estado="<?php echo e(e($pedido['estado'])); ?>">
                                        <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Ver
                                    </button>

                                    <form action="api/pedidos_actions.php" method="POST" class="inline deliver-form ml-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="deliver">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="flex items-center text-sm font-medium text-green-600 bg-green-100 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 px-3 py-1 rounded-lg">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i> Entregar
                                        </button>
                                    </form>
                                   
                                    <form action="api/pedidos_actions.php" method="POST" class="inline cancel-form ml-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="flex items-center text-sm font-medium text-red-600 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 px-3 py-1 rounded-lg">
                                            <i data-lucide="x-circle" class="w-4 h-4 mr-1"></i> Cancelar
                                        </button>
                                    </form>

                                <?php else: ?>
                                    <!-- Otros estados (Entregado, Cancelado): Solo Ver -->
                                    <button class="view-order-btn flex items-center text-sm font-medium text-blue-600 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 px-3 py-1 rounded-lg"
                                            data-id="<?php echo e($pedido['id_pedido']); ?>"
                                            data-cliente-id="<?php echo e($pedido['id_cliente']); ?>"
                                            data-cliente-nombre="<?php echo e(e($pedido['nombre_razon_social'])); ?>"
                                            data-fecha="<?php echo e($pedido['fecha_cotizacion']); ?>"
                                            data-direccion="<?php echo e(e($pedido['direccion_entrega'])); ?>"
                                            data-estado="<?php echo e(e($pedido['estado'])); ?>">
                                        <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Ver
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                             <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <i data-lucide="clipboard-list" class="w-8 h-8 text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <?php if (!empty($search_term)): ?>
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">Sin resultados</p>
                                    <p class="text-sm">No se encontraron pedidos que coincidan con "<strong><?php echo e($search_term); ?></strong>".</p>
                                    <a href="index.php?page=pedidos" class="mt-2 text-green-600 hover:text-green-700 font-medium text-sm">Limpiar búsqueda</a>
                                <?php else: ?>
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No hay pedidos</p>
                                    <p class="text-sm">Aún no se han registrado pedidos en el sistema.</p>
                                    <button class="mt-2 text-green-600 hover:text-green-700 font-medium text-sm" onclick="document.getElementById('add-order-btn').click()">
                                        Crear el primer pedido
                                    </button>
                                <?php endif; ?>
                            </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando <span class="font-semibold text-gray-800 dark:text-white"><?php echo $total_records > 0 ? $offset + 1 : 0; ?></span> 
            a <span class="font-semibold text-gray-800 dark:text-white"><?php echo min($total_records, $offset + $limit); ?></span> 
            de <span class="font-semibold text-gray-800 dark:text-white"><?php echo $total_records; ?></span> registros
        </div>

        <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Paginación">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo pagination_url($current_page - 1, $search_term); ?>" class="relative inline-flex items-center rounded-l-md px-3 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center rounded-l-md px-3 py-2 text-gray-300 dark:text-gray-600 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 cursor-not-allowed">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </span>
            <?php endif; ?>

            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-gray-50 dark:bg-gray-800">
                Página <?php echo $current_page; ?> de <?php echo max(1, $total_pages); ?>
            </span>

            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo pagination_url($current_page + 1, $search_term); ?>" class="relative inline-flex items-center rounded-r-md px-3 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center rounded-r-md px-3 py-2 text-gray-300 dark:text-gray-600 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 cursor-not-allowed">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div id="order-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 flex items-center justify-center hidden z-30 transition-opacity duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-4xl max-h-[90vh] flex flex-col transform transition-all duration-300 scale-100">
        <div class="flex justify-between items-center mb-6 border-b dark:border-gray-700 pb-4">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Crear Nuevo Pedido</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
       
        <form id="order-form" class="flex-grow flex flex-col overflow-hidden">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id_pedido" id="form-order-id" value="">
            <input type="hidden" name="detalle_pedido" id="detalle_pedido_json">

            <div id="modal-message-container"></div>
            <div id="view-mode-status" class="hidden"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="relative">
                    <label for="cliente_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                    <input type="text" id="cliente_search" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-shadow" placeholder="Buscar cliente..." autocomplete="off">
                    <input type="hidden" id="id_cliente" name="id_cliente">
                    <div id="cliente_search_results" class="absolute z-50 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto hidden"></div>
                </div>
                <div>
                    <label for="fecha_cotizacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                    <input type="date" id="fecha_cotizacion" name="fecha_cotizacion" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-shadow">
                </div>
                <div class="md:col-span-2">
                    <label for="direccion_entrega" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección de Entrega</label>
                    <input type="text" id="direccion_entrega" name="direccion_entrega" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 transition-shadow" disabled>
                </div>
            </div>

            <div id="add-item-section">
                <div class="bg-gray-50 dark:bg-gray-700/50 border dark:border-gray-600 rounded-lg p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                    <div class="lg:col-span-4 relative sm:col-span-2">
                        <label for="producto_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Producto</label>
                        <input type="text" id="producto_search" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Buscar producto..." autocomplete="off">
                        <input type="hidden" id="id_producto_seleccionado">
                        <div id="producto_search_results" class="absolute z-50 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto hidden"></div>
                    </div>
                     <div class="lg:col-span-2">
                        <label for="unidad_medida_display" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Unidad</label>
                        <input type="text" id="unidad_medida_display" class="w-full px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-lg select-none" readonly tabindex="-1">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="peso_neto_display" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Peso</label>
                        <input type="text" id="peso_neto_display" class="w-full px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 border border-gray-200 dark:border-gray-500 rounded-lg select-none" readonly tabindex="-1">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="cantidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cantidad</label>
                        <input type="number" id="cantidad" min="1" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="precio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Precio Unit.</label>
                        <input type="number" id="precio" min="0.01" step="0.01" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="lg:col-span-12 sm:col-span-2 flex justify-end mt-2">
                        <button type="button" id="add-item-btn" class="flex items-center justify-center bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0">
                            <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i> Agregar Item
                        </button>
                    </div>
                </div>
            </div>
           
            <div class="flex-grow overflow-y-auto mt-6 border dark:border-gray-700 rounded-lg">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-white z-10 shadow-sm">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Producto</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Cantidad</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Precio Unit.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider">Subtotal</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="detalle-pedido-body" class="divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                </table>
            </div>

            <div class="mt-6 pt-4 border-t dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-baseline">
                    <span class="text-xl font-bold text-gray-600 dark:text-gray-300">TOTAL:</span>
                    <span id="total-pedido" class="text-3xl font-extrabold ml-3 text-gray-900 dark:text-white tracking-tight">$ 0.00</span>
                </div>
                <div class="flex space-x-3 w-full sm:w-auto">
                    <button type="button" id="cancel-btn" class="flex-1 sm:flex-none justify-center px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" id="submit-btn" class="flex-1 sm:flex-none justify-center px-6 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Crear Pedido
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>