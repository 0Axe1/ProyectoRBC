<?php
// pages/inventario.php
// $pdo está disponible desde index.php

// --- LÓGICA PARA MOSTRAR MENSAJES ---
$mensaje = '';
if (isset($_GET['success'])) {
    $codigos_exito = [
        '1' => 'Producto y detalles registrados exitosamente.',
        '2' => 'Producto y detalles actualizados exitosamente.',
        '3' => 'Producto eliminado del inventario.',
    ];
    $mensaje = "<div class='mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . e($codigos_exito[$_GET['success']]) . "</div>";
}
if (isset($_GET['error'])) {
    $codigos_error = [
        '1T' => 'Error. Faltan datos obligatorios (Nombre, Stock o Precio).',
        '2' => 'No se pudo procesar la solicitud en la base de datos.',
        '3' => 'No se pudo eliminar el producto. Puede que esté asociado a un pedido o venta.',
        'csrf' => 'Error de seguridad al procesar la solicitud. Por favor, recargue la página e intente de nuevo.',
        'permiso' => 'No tiene autorización para realizar esta acción.',
        'db_conn' => 'Error de conexión a la base de datos.',
    ];
    $error_msg = $codigos_error[$_GET['error']] ?? 'Hubo un error al realizar la operación.';
    $mensaje = "<div class='mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg'>" . e($error_msg) . "</div>";
}


// --- 1. Configuración de Paginación y Búsqueda ---
$limit = 10; // Registros por página
$low_stock_threshold = 50; // Umbral de stock bajo
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $limit;

$search_term = trim($_GET['search'] ?? '');
$params = [];

// --- 2. Consulta para obtener el total de registros (Paginación) ---
$sql_count = "SELECT COUNT(*) 
              FROM productos p
              WHERE p.activo = 1";

if (!empty($search_term)) {
    $sql_count .= " AND (p.nombre_producto LIKE ?)";
    $count_params = ['%' . $search_term . '%'];
} else {
    $count_params = [];
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($count_params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Ajuste de seguridad
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

// --- 3. Consulta Principal con LIMIT y OFFSET ---
$sql = "SELECT
            p.id_producto,
            p.nombre_producto,
            p.precio, 
            p.stock,  
            dp.descripcion,
            dp.unidad_medida,
            dp.peso_neto,
            dp.link_documentos
        FROM productos p
        LEFT JOIN detalle_producto dp ON p.id_producto = dp.id_producto
        WHERE p.activo = 1";

if (!empty($search_term)) {
    $sql .= " AND (p.nombre_producto LIKE ?)";
    $search_like = '%' . $search_term . '%';
    $params[] = $search_like;
}

$sql .= " ORDER BY p.nombre_producto ASC LIMIT $limit OFFSET $offset";

$stmt_inventario = $pdo->prepare($sql);
$stmt_inventario->execute($params);

// Función auxiliar para mantener parámetros de búsqueda en los links de paginación
function pagination_url($page_num, $search) {
    // Nota: 'index.php?page=inventario' es la base, ajusta si tu router es diferente
    $url = "?page=inventario&p=" . $page_num;
    if (!empty($search)) {
        $url .= "&search=" . urlencode($search);
    }
    return $url;
}

?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">
    <div id="message-container"><?php echo $mensaje; ?></div>

    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Control de Inventario</h3>
        <button id="add-product-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Registrar Nuevo Producto
        </button>
    </div>

    <!-- Buscador -->
    <div class="mb-4">
        <form action="index.php" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="page" value="inventario">
            <div class="relative flex-grow w-full sm:w-auto">
                <label for="search" class="sr-only">Buscar producto</label>
                <input type="text" id="search" name="search"
                       class="w-full px-4 py-2 pl-10 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Buscar por Nombre..."
                       value="<?php echo e($search_term); ?>">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </span>
            </div>
            <button type="submit" class="w-full sm:w-auto px-4 py-2 flex items-center justify-center bg-green-600 text-white rounded-lg hover:bg-green-700 transition-transform duration-300 hover:scale-105">
                Buscar
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php?page=inventario" class="w-full sm:w-auto px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">Producto</th>
                    <th scope="col" class="px-6 py-3">Stock (Cantidad)</th>
                    <th scope="col" class="px-6 py-3">Precio (Bs.)</th>
                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stmt_inventario && $stmt_inventario->rowCount() > 0) : ?>
                    <?php while($item = $stmt_inventario->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($item['nombre_producto']); ?></td>
                            <td class="px-6 py-4 font-bold <?php echo ($item['stock'] ?? 0) < $low_stock_threshold ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'; ?>"><?php echo e($item['stock'] ?? 0); ?></td>
                            <td class="px-6 py-4">Bs. <?php echo number_format($item['precio'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4 flex space-x-2 justify-end">
                                
                                <!-- ¡NUEVO! Botón "Ver" -->
                                <button class="view-btn flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/50 dark:text-gray-400 dark:hover:bg-gray-700/80 px-3 py-1 rounded-lg"
                                        title="Ver Detalles"
                                        data-id="<?php echo e($item['id_producto']); ?>"
                                        data-nombre="<?php echo e($item['nombre_producto']); ?>"
                                        data-precio="<?php echo e($item['precio'] ?? 0); ?>"
                                        data-stock="<?php echo e($item['stock'] ?? 0); ?>"
                                        data-descripcion="<?php echo e($item['descripcion'] ?? ''); ?>"
                                        data-unidad-medida="<?php echo e($item['unidad_medida'] ?? ''); ?>"
                                        data-peso-neto="<?php echo e($item['peso_neto'] ?? ''); ?>"
                                        data-link-documentos="<?php echo e($item['link_documentos'] ?? ''); ?>">
                                    <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Ver
                                </button>
                                
                                <!-- Data-attributes para detalle_producto -->
                                <button class="edit-btn flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 dark:hover:bg-blue-900/80 px-3 py-1 rounded-lg"
                                        title="Editar Información del Producto"
                                        data-id="<?php echo e($item['id_producto']); ?>"
                                        data-nombre="<?php echo e($item['nombre_producto']); ?>"
                                        data-precio="<?php echo e($item['precio'] ?? 0); ?>"
                                        data-stock="<?php echo e($item['stock'] ?? 0); ?>"
                                        data-descripcion="<?php echo e($item['descripcion'] ?? ''); ?>"
                                        data-unidad-medida="<?php echo e($item['unidad_medida'] ?? ''); ?>"
                                        data-peso-neto="<?php echo e($item['peso_neto'] ?? ''); ?>"
                                        data-link-documentos="<?php echo e($item['link_documentos'] ?? ''); ?>">
                                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Editar
                                </button>
                               
                                <form action="api/inventario_actions.php" method="POST" class="inline delete-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($item['id_producto']); ?>">
                                    <button type="submit" class="flex items-center text-sm font-medium text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/80 px-3 py-1 rounded-lg" title="Eliminar Producto">
                                        <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Borrar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="bg-gray-100 dark:bg-gray-700 rounded-full p-4">
                                    <i data-lucide="package-open" class="w-10 h-10 text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">No hay productos en el inventario.</p>
                                <?php if (!empty($search_term)): ?>
                                    <a href="index.php?page=inventario" class="text-sm text-green-600 hover:text-green-700 dark:text-green-400 hover:underline">Limpiar búsqueda</a>
                                <?php else: ?>
                                    <p class="text-sm text-gray-400 dark:text-gray-500">Comience registrando un nuevo producto.</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Mostrando <span class="font-semibold text-gray-800 dark:text-white"><?php echo $total_records > 0 ? $offset + 1 : 0; ?></span> 
            a <span class="font-semibold text-gray-800 dark:text-white"><?php echo min($total_records, $offset + $limit); ?></span> 
            de <span class="font-semibold text-gray-800 dark:text-white"><?php echo $total_records; ?></span> registros
        </div>

        <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Paginación">
            <!-- Botón Anterior -->
            <?php if ($current_page > 1): ?>
                <a href="<?php echo pagination_url($current_page - 1, $search_term); ?>" class="relative inline-flex items-center rounded-l-md px-3 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center rounded-l-md px-3 py-2 text-gray-300 dark:text-gray-600 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 cursor-not-allowed">
                    <i data-lucide="chevron-left" class="w-5 h-5"></i>
                </span>
            <?php endif; ?>

            <!-- Info Página -->
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 bg-gray-50 dark:bg-gray-800">
                Página <?php echo $current_page; ?> de <?php echo max(1, $total_pages); ?>
            </span>

            <!-- Botón Siguiente -->
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

<!-- MODAL PARA AGREGAR/EDITAR PRODUCTO (ADAPTADO) -->
<div id="product-modal" class="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/80 backdrop-blur-sm flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Registrar Nuevo Producto</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        <div id="modal-message-container"></div>
        <form id="product-form" action="api/inventario_actions.php" method="POST" class="flex-grow overflow-y-auto pr-2">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id_producto" id="form-product-id" value="">

            <div class="space-y-4">
                <!-- Fila 1: Nombre (Categoría eliminada) -->
                 <div>
                    <label for="nombre_producto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Producto *</label>
                    <input type="text" id="nombre_producto" name="nombre_producto" required class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>

                <!-- Fila 2: Stock y Precio -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div id="stock-container">
                        <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stock *</label>
                        <input type="number" id="stock" name="stock" required min="0" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="precio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Precio (Bs.) *</label>
                        <input type="number" id="precio" name="precio" required min="0.01" step="0.01" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    </div>
                </div>
                
                <div id="additional-details-container" class="hidden">
                    <hr class="dark:border-gray-600">
                    <h5 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Detalles Adicionales</h5>

                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="2" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="unidad_medida" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unidad de Medida</label>
                            <input type="text" id="unidad_medida" name="unidad_medida" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="Ej: kg, L, Unid.">
                        </div>
                        <div>
                            <label for="peso_neto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peso Neto</label>
                            <input type="number" id="peso_neto" name="peso_neto" min="0" step="0.01" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        </div>
                    </div>
                    
                    <div>
                        <label for="link_documentos" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Link Documentos</label>
                        <input type="text" id="link_documentos" name="link_documentos" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" placeholder="Ej: https://drive.google.com/...">
                    </div>
                </div>

                <!-- Toggle para detalles adicionales -->
                <button type="button" id="toggle-details-btn" class="mt-2 text-sm text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 flex items-center gap-1 transition-colors">
                    <i data-lucide="chevron-down" class="w-4 h-4" id="toggle-details-icon"></i>
                    <span id="toggle-details-text">Mostrar detalles adicionales</span>
                </button>
            </div>

            <div class="mt-8 flex justify-end space-x-4 pt-4 border-t dark:border-gray-700">
                <button type="button" id="cancel-btn" class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">Cancelar</button>
                <button type="submit" id="submit-btn" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>