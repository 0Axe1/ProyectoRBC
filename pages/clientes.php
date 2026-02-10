<?php
// ¡CORREGIDO!
// Se eliminaron session_start(), function e() y la conexión PDO.
// Ahora este archivo espera que $pdo exista desde index.php

// --- 1. Configuración de Paginación y Búsqueda ---
$limit = 10; // Registros por página
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $limit;

// --- ¡NUEVO! Lógica de Búsqueda ---
$search_term = trim($_GET['search'] ?? '');
$params = [];
// --- Fin de lo nuevo ---


// --- 2. Consulta para obtener el total de registros (Paginación) ---
$sql_count = "SELECT COUNT(*) FROM clientes c WHERE c.estado = 1";

if (!empty($search_term)) {
    $sql_count .= " AND (c.nombre_razon_social LIKE ? OR c.nit_ruc LIKE ?)";
    $count_params = ['%' . $search_term . '%', '%' . $search_term . '%'];
} else {
    $count_params = [];
}

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($count_params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Ajuste de seguridad
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

// --- ¡MODIFICADO! CONSULTA OPTIMIZADA SIN CONTACTOS EN LA PRINCIPAL ---
$sql = "SELECT
            c.id_cliente,
            c.nombre_razon_social,
            c.nit_ruc,
            c.ubicacion,
            DATE_FORMAT(c.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_fmt,
            DATE_FORMAT(c.fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion_fmt
        FROM
            clientes c
        WHERE
            c.estado = 1";

// --- ¡NUEVO! Añadir condición de búsqueda ---
if (!empty($search_term)) {
    // Busca en nombre_razon_social O en nit_ruc
    $sql .= " AND (c.nombre_razon_social LIKE ? OR c.nit_ruc LIKE ?)";
    $search_like = '%' . $search_term . '%';
    // Añadimos el parámetro dos veces (uno para nombre, uno para nit)
    $params[] = $search_like;
    $params[] = $search_like;
}
// --- Fin de lo nuevo ---
        
$sql .= " ORDER BY c.nombre_razon_social ASC LIMIT $limit OFFSET $offset";
         
// --- ¡MODIFICADO! Usar prepared statement para la búsqueda ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes_data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all client data

// --- ¡NUEVO! Cargar todos los contactos para los clientes obtenidos ---
$cliente_ids = array_column($clientes_data, 'id_cliente');
$contacts_by_client = [];

if (!empty($cliente_ids)) {
    $placeholders = implode(',', array_fill(0, count($cliente_ids), '?'));
    $sql_contacts = "SELECT cc.id_cliente, cc.dato_contacto, t.nombre_tipo
                     FROM contactos_cliente cc
                     JOIN tipos_contacto t ON cc.id_tipo_contacto = t.id_tipo_contacto
                     WHERE cc.id_cliente IN ($placeholders)";
    $stmt_contacts = $pdo->prepare($sql_contacts);
    $stmt_contacts->execute($cliente_ids);
    
    while ($contact = $stmt_contacts->fetch(PDO::FETCH_ASSOC)) {
        $contacts_by_client[$contact['id_cliente']][] = $contact;
    }
}

// --- ¡NUEVO! Procesar clientes para adjuntar teléfonos y emails ---
foreach ($clientes_data as &$cliente) {
    $cliente['telefonos'] = [];
    $cliente['emails'] = [];
    if (isset($contacts_by_client[$cliente['id_cliente']])) {
        foreach ($contacts_by_client[$cliente['id_cliente']] as $contact) {
            if (strpos(strtolower($contact['nombre_tipo']), 'telefono') !== false) {
                $cliente['telefonos'][] = $contact['dato_contacto'];
            } elseif (strpos(strtolower($contact['nombre_tipo']), 'email') !== false) {
                $cliente['emails'][] = $contact['dato_contacto'];
            }
        }
    }
}
unset($cliente); // Unset the reference

// Se obtiene los tipos de contacto, pero ya no se usan directamente en el formulario de edición
$stmt_tipos = $pdo->query("SELECT id_tipo_contacto, nombre_tipo FROM tipos_contacto ORDER BY nombre_tipo");
$tipos_contacto = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">
    <!-- ¡NUEVO! Contenedor de mensajes ahora será manejado por JS -->
    <div id="message-container"></div>

    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Listado de Clientes</h3>
        <button id="add-client-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Agregar Cliente
        </button>
    </div>

    <!-- ¡NUEVO! Formulario de Búsqueda -->
    <div class="mb-4">
        <form action="index.php" method="GET" class="flex flex-wrap items-center gap-2">
            <!-- Campo oculto para mantener la página actual al buscar -->
            <input type="hidden" name="page" value="clientes">
            
            <div class="relative flex-grow w-full sm:w-auto">
                <label for="search" class="sr-only">Buscar cliente</label>
                <input type="text" id="search" name="search"
                       class="w-full px-4 py-2 pl-10 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Buscar por Nombre o NIT/RUC..."
                       value="<?php echo e($search_term); // Mantiene el valor buscado en el input ?>">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </span>
            </div>
            <button type="submit" class="w-full sm:w-auto px-4 py-2 flex items-center justify-center bg-green-600 text-white rounded-lg hover:bg-green-700 transition-transform duration-300 hover:scale-105">
                <i data-lucide="search" class="w-4 h-4 mr-2 sm:hidden"></i> <!-- Icono para móvil -->
                Buscar
            </button>
            <!-- Botón para limpiar la búsqueda -->
            <?php if (!empty($search_term)): ?>
                <a href="index.php?page=clientes" class="w-full sm:w-auto px-4 py-2 text-sm text-center text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>
    <!-- Fin del Formulario de Búsqueda -->

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border-b dark:border-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Nombre / Razón Social</th>
                    <th scope="col" class="px-6 py-4 font-semibold">NIT / RUC</th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <!-- ¡NUEVO! Icono de diseño -->
                        <i data-lucide="phone" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Teléfono
                    </th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <!-- ¡NUEVO! Icono de diseño -->
                        <i data-lucide="mail" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Email
                    </th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <!-- ¡NUEVO! Icono de diseño -->
                        <i data-lucide="map-pin" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Ubicación
                    </th>
                    <!-- ¡NUEVO! Columnas de fecha -->
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <i data-lucide="calendar-plus" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Creado
                    </th>
                    <th scope="col" class="px-6 py-4 font-semibold">
                        <i data-lucide="calendar-clock" class="w-4 h-4 inline-block mr-1 opacity-70"></i>
                        Actualizado
                    </th>
                    <!-- Fin de lo nuevo -->
                    <th scope="col" class="px-6 py-4 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clientes_data && count($clientes_data) > 0) : ?>
                    <?php foreach($clientes_data as $cliente): ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($cliente['nombre_razon_social']); ?></td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400"><?php echo e($cliente['nit_ruc'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400"><?php echo e(implode(', ', $cliente['telefonos'] ?? ['N/A'])); ?></td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400"><?php echo e(implode(', ', $cliente['emails'] ?? ['N/A'])); ?></td>
                            <td class="px-6 py-4"><?php echo e($cliente['ubicacion']); ?></td>
                            
                            <!-- ¡NUEVO! Celdas de fecha -->
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <?php echo e($cliente['fecha_creacion_fmt']); ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <?php echo e($cliente['fecha_actualizacion_fmt']); ?>
                            </td>
                            <!-- Fin de lo nuevo -->

                            <td class="px-6 py-4 flex space-x-2 justify-end">
                                <button class="edit-btn flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 dark:hover:bg-blue-900/80 px-3 py-1 rounded-lg"
                                        title="Editar Cliente"
                                        data-id="<?php echo e($cliente['id_cliente']); ?>"
                                        data-nombre="<?php echo e($cliente['nombre_razon_social']); ?>"
                                        data-nit="<?php echo e($cliente['nit_ruc'] ?? ''); ?>"
                                        data-ubicacion="<?php echo e($cliente['ubicacion']); ?>"
                                        data-telefono="<?php echo e(implode(', ', $cliente['telefonos'] ?? '')); ?>"
                                        data-email="<?php echo e(implode(', ', $cliente['emails'] ?? '')); ?>">
                                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Editar
                                </button>
                               
                                <!-- ¡CAMBIO! Se quitó onsubmit y se agregó clase 'delete-form' -->
                                <form action="api/clientes_actions.php" method="POST" class="inline delete-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($cliente['id_cliente']); ?>">
                                   
                                    <button type="submit" class="flex items-center text-sm font-medium text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/80 px-3 py-1 rounded-lg" title="Eliminar Cliente">
                                        <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <!-- ¡NUEVO! Colspan actualizado de 5 a 8 -->
                    <!-- ¡NUEVO! Mensaje dinámico si no hay resultados de búsqueda -->
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <i data-lucide="users" class="w-8 h-8 text-gray-400 dark:text-gray-500"></i>
                                </div>
                                
                                <?php if (!empty($search_term)): ?>
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">Sin resultados</p>
                                    <p class="text-sm">No se encontraron clientes que coincidan con "<strong><?php echo e($search_term); ?></strong>".</p>
                                    <a href="index.php?page=clientes" class="mt-2 text-green-600 hover:text-green-700 font-medium text-sm">Limpiar búsqueda</a>
                                <?php else: ?>
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No hay clientes</p>
                                    <p class="text-sm">Aún no se han registrado clientes en el sistema.</p>
                                    <button class="mt-2 text-green-600 hover:text-green-700 font-medium text-sm" onclick="document.getElementById('add-client-btn').click()">
                                        Agregar el primer cliente
                                    </button>
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

<?php
// Función auxiliar para mantener parámetros de búsqueda en los links de paginación
function pagination_url($page_num, $search) {
    // Nota: 'index.php?page=clientes' es la base
    $url = "?page=clientes&p=" . $page_num;
    if (!empty($search)) {
        $url .= "&search=" . urlencode($search);
    }
    return $url;
}
?>

<!-- MODAL PARA AGREGAR/EDITAR CLIENTE -->
<div id="client-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-lg transform transition-all duration-300 ease-out">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Agregar Nuevo Cliente</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
       
        <form id="client-form" action="api/clientes_actions.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id_cliente" id="form-client-id" value="">

            <!-- ¡NUEVO! Contenedor de mensajes de error DENTRO del modal -->
            <div id="modal-message-container"></div>

            <div class="space-y-6">
                <div>
                    <label for="nombre_razon_social" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre / Razón Social</label>
                    <input type="text" id="nombre_razon_social" name="nombre_razon_social" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="nit_ruc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NIT / RUC</label>
                    <input type="text" id="nit_ruc" name="nit_ruc" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="ubicacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
               
                <!-- ¡MODIFICADO! Se eliminan los campos de tipo y dato de contacto genéricos -->
                <!-- Se añaden campos específicos para teléfono y email -->
                <div>
                    <label for="telefono_contacto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
                    <input type="tel" id="telefono_contacto" name="telefono_contacto" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Ej: 71234567 (solo números)" pattern="^[0-9]{7,15}$" maxLength="15">
                </div>
                <div>
                    <label for="email_contacto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" id="email_contacto" name="email_contacto" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="ejemplo@correo.com" maxLength="100">
                </div>
            </div>

            <div class="flex justify-end mt-8 space-x-4">
                <button type="button" id="cancel-btn" class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">Cancelar</button>
                <button type="submit" id="submit-btn" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>