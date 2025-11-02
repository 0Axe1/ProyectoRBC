<?php
// ¡CORREGIDO!
// Se eliminaron require_once, session_start() y la conexión $db.
// Ahora este archivo espera que $pdo exista desde index.php

// --- LÓGICA PARA MOSTRAR MENSAJES ---
$mensaje = '';
if (isset($_GET['success'])) {
    $codigos_exito = [
        '1' => 'Producto y stock inicial registrados exitosamente.',
        '2' => 'Producto actualizado exitosamente.',
        '3' => 'Producto eliminado del inventario.',
    ];
    $mensaje = "<div class='mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . e($codigos_exito[$_GET['success']]) . "</div>";
}
if (isset($_GET['error'])) {
    $codigos_error = [
        '1T' => 'Error al crear el producto. Faltan datos o son incorrectos.',
        '2' => 'No se pudo registrar el producto. Intente de nuevo.',
        '3' => 'No se pudo eliminar el producto. Puede que esté asociado a un pedido o venta.',
        'csrf' => 'Error de seguridad al procesar la solicitud. Por favor, recargue la página e intente de nuevo.',
        'permiso' => 'No tiene autorización para realizar esta acción.',
        'db_conn' => 'Error de conexión a la base de datos.',
    ];
    $error_msg = $codigos_error[$_GET['error']] ?? 'Hubo un error al realizar la operación.';
    $mensaje = "<div class='mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg'>" . e($error_msg) . "</div>";
}

// --- ¡MODIFICADO! CONSULTA AHORA USA $pdo ---
$sql = "SELECT
            p.id_producto,
            p.nombre_producto,
            p.descripcion_producto,
            p.precio_venta_sugerido,
            p.cantidad_stock,
            c.id_categoria,
            c.nombre_categoria
        FROM productos p
        JOIN categorias_producto c ON p.id_categoria = c.id_categoria
        ORDER BY p.nombre_producto ASC";

$stmt_inventario = $pdo->query($sql);

// --- ¡MODIFICADO! CONSULTA AHORA USA $pdo ---
$stmt_categorias = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias_producto ORDER BY nombre_categoria");
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md fade-in">
    <div id="message-container"><?php echo $mensaje; // $mensaje ya está escapado ?></div>

    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Control de Inventario</h3>
        <button id="add-product-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Registrar Nuevo Producto
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">Producto</th>
                    <th scope="col" class="px-6 py-3">Categoría</th>
                    <th scope="col" class="px-6 py-3">Stock (Cantidad)</th>
                    <th scope="col" class="px-6 py-3">Precio Venta (Bs.)</th>
                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- ¡MODIFICADO! Bucle PDO -->
                <?php if ($stmt_inventario && $stmt_inventario->rowCount() > 0) : ?>
                    <?php while($item = $stmt_inventario->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($item['nombre_producto']); ?></td>
                            <td class="px-6 py-4"><?php echo e($item['nombre_categoria']); ?></td>
                            <td class="px-6 py-4 font-bold <?php echo ($item['cantidad_stock'] ?? 0) < 50 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'; ?>"><?php echo e($item['cantidad_stock'] ?? 0); ?></td>
                            <td class="px-6 py-4">Bs. <?php echo number_format($item['precio_venta_sugerido'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4 flex space-x-2 justify-end">
                                <button class="edit-btn flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded-lg"
                                        title="Editar Información del Producto"
                                        data-id="<?php echo e($item['id_producto']); ?>"
                                        data-nombre="<?php echo e($item['nombre_producto']); ?>"
                                        data-id-categoria="<?php echo e($item['id_categoria']); ?>"
                                        data-descripcion="<?php echo e($item['descripcion_producto']); ?>"
                                        data-precio-venta="<?php echo e($item['precio_venta_sugerido'] ?? 0); ?>"
                                        data-stock="<?php echo e($item['cantidad_stock'] ?? 0); ?>">
                                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Editar
                                </button>
                               
                                <form action="api/inventario_actions.php" method="POST" class="inline" onsubmit="return confirm('¿Seguro que quieres eliminar este producto? No se puede borrar si está asociado a un pedido.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($item['id_producto']); ?>">
                                    <button type="submit" class="flex items-center text-sm font-medium text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-lg" title="Eliminar Producto">
                                        <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Borrar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr><td colspan="5" class="px-6 py-4 text-center">No hay productos en el inventario.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA AGREGAR/EDITAR PRODUCTO (ADAPTADO) -->
<div id="product-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 w-full max-w-lg">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Registrar Nuevo Producto</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
       
        <form id="product-form" action="api/inventario_actions.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id_producto" id="form-product-id" value="">

            <div class="space-y-4">
                <div>
                    <label for="nombre_producto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Producto</label>
                    <!-- ¡CAMBIO! Clases de Tailwind aplicadas -->
                    <input type="text" id="nombre_producto" name="nombre_producto" required class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                 <div>
                    <label for="id_categoria" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoría</label>
                    <!-- ¡CAMBIO! Clases de Tailwind aplicadas -->
                    <select id="id_categoria" name="id_categoria" required class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo e($categoria['id_categoria']); ?>">
                                <?php echo e($categoria['nombre_categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div>
                    <label for="descripcion_producto" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                    <!-- ¡CAMBIO! Clases de Tailwind aplicadas -->
                    <textarea id="descripcion_producto" name="descripcion_producto" rows="2" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div id="stock-container">
                        <label for="cantidad_stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stock Inicial</label>
                        <!-- ¡CAMBIO! Clases de Tailwind aplicadas -->
                        <input type="number" id="cantidad_stock" name="cantidad_stock" required min="0" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    </div>
                    <div>
                        <label for="precio_venta_sugerido" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Precio Venta (Bs.)</label>
                        <!-- ¡CAMBIO! Clases de Tailwind aplicadas -->
                        <input type="number" id="precio_venta_sugerido" name="precio_venta_sugerido" required min="0.01" step="0.01" class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end space-x-4">
                <button type="button" id="cancel-btn" class="px-6 py-2 text-sm font-medium bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200">Cancelar</button>
                <button type="submit" id="submit-btn" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- 
¡CAMBIO! El bloque <style> que estaba aquí fue eliminado.
El bloque <script> ya fue eliminado en el paso anterior.
-->

