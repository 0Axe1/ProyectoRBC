<?php
// pages/categorias.php
// $pdo está disponible desde index.php

// --- Lógica de Búsqueda ---
$search_term = trim($_GET['search'] ?? '');
$params = [];

$sql = "SELECT id_categoria, nombre_categoria, descripcion 
        FROM categorias_producto";

if (!empty($search_term)) {
    $sql .= " WHERE nombre_categoria LIKE ? OR descripcion LIKE ?";
    $params[] = '%' . $search_term . '%';
    $params[] = '%' . $search_term . '%';
}
$sql .= " ORDER BY nombre_categoria ASC";
         
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md fade-in">
    <!-- Contenedor de mensajes -->
    <div id="message-container"></div>

    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Gestión de Categorías</h3>
        <button id="add-categoria-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Agregar Categoría
        </button>
    </div>

    <!-- Formulario de Búsqueda -->
    <div class="mb-4">
        <form action="index.php" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="page" value="categorias">
            
            <div class="relative flex-grow">
                <label for="search" class="sr-only">Buscar categoría</label>
                <input type="text" id="search" name="search"
                       class="w-full px-4 py-2 pl-10 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                       placeholder="Buscar por Nombre o Descripción..."
                       value="<?php echo e($search_term); ?>">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </span>
            </div>
            <button type="submit" class="px-4 py-2 flex items-center justify-center bg-green-600 text-white rounded-lg hover:bg-green-700">
                Buscar
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php?page=categorias" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                    Limpiar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">Nombre de Categoría</th>
                    <th scope="col" class="px-6 py-3">Descripción</th>
                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stmt && $stmt->rowCount() > 0) : ?>
                    <?php while($cat = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($cat['nombre_categoria']); ?></td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400"><?php echo e($cat['descripcion'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 flex space-x-2 justify-end">
                                <button class="edit-btn flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 dark:hover:bg-blue-900/80 px-3 py-1 rounded-lg"
                                        title="Editar Categoría"
                                        data-id="<?php echo e($cat['id_categoria']); ?>"
                                        data-nombre="<?php echo e($cat['nombre_categoria']); ?>"
                                        data-descripcion="<?php echo e($cat['descripcion'] ?? ''); ?>">
                                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Editar
                                </button>
                               
                                <form action="api/categorias_actions.php" method="POST" class="inline delete-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($cat['id_categoria']); ?>">
                                   
                                    <button type="submit" class="flex items-center text-sm font-medium text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/80 px-3 py-1 rounded-lg" title="Eliminar Categoría">
                                        <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="3" class="px-6 py-4 text-center">
                            <?php if (!empty($search_term)): ?>
                                No se encontraron categorías que coincidan con "<?php echo e($search_term); ?>".
                            <?php else: ?>
                                No hay categorías registradas.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA AGREGAR/EDITAR CATEGORÍA -->
<div id="categoria-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 w-full max-w-lg transform transition-all duration-300 ease-out">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Agregar Nueva Categoría</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
       
        <form id="categoria-form" action="api/categorias_actions.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id_categoria" id="form-categoria-id" value="">

            <!-- Contenedor de mensajes de error DENTRO del modal -->
            <div id="modal-message-container"></div>

            <div class="space-y-6">
                <div>
                    <label for="nombre_categoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de Categoría *</label>
                    <input type="text" id="nombre_categoria" name="nombre_categoria" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end mt-8 space-x-4">
                <button type="button" id="cancel-btn" class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">Cancelar</button>
                <button type="submit" id="submit-btn" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Guardar Categoría</button>
            </div>
        </form>
    </div>
</div>