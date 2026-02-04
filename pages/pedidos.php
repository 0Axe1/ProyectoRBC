<?php
// ¡CORREGIDO!
// Se eliminaron la lógica de mensajes GET, ahora se maneja por JS.
// La conexión $pdo se espera desde index.php

// --- ¡MODIFICADO! CONSULTA CON FECHAS Y DATOS PARA EDICIÓN ---
$sql = "SELECT
            ped.id_pedido,
            cli.nombre_razon_social,
            ped.id_cliente, -- ¡NUEVO! para editar
            ped.direccion_entrega, -- ¡NUEVO! para editar
            ped.fecha_cotizacion,
            -- ¡NUEVO! Fechas formateadas
            DATE_FORMAT(ped.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_fmt,
            DATE_FORMAT(ped.fecha_actualizacion, '%d/%m/%Y %H:%i') as fecha_actualizacion_fmt,
            est.nombre_estado AS estado,
            (SELECT SUM(precio_total_cotizado)
             FROM detalle_de_pedido
             WHERE id_pedido = ped.id_pedido) as total_pedido
        FROM pedidos ped
        JOIN clientes cli ON ped.id_cliente = cli.id_cliente
        JOIN estados_pedido est ON ped.id_estado_pedido = est.id_estado_pedido
        ORDER BY ped.fecha_cotizacion DESC, ped.id_pedido DESC";
$stmt = $pdo->query($sql);
?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">
    <!-- ¡NUEVO! Contenedor de mensajes (manejado por JS) -->
    <div id="message-container"></div>

    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Gestión de Pedidos</h3>
        <button id="add-order-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
            Nuevo Pedido
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Cliente</th>
                    <th scope="col" class="px-6 py-3">Fecha Cotiz.</th>
                    <th scope="col" class="px-6 py-3">Total</th>
                    <th scope="col" class="px-6 py-3">Estado</th>
                    <!-- ¡NUEVO! Columnas de fecha -->
                    <th scope="col" class="px-6 py-3">Creado</th>
                    <th scope="col" class="px-6 py-3">Actualizado</th>
                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
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
                            
                            <!-- ¡NUEVO! Celdas de fecha -->
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <?php echo e($pedido['fecha_creacion_fmt']); ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <?php echo e($pedido['fecha_actualizacion_fmt']); ?>
                            </td>

                            <td class="px-6 py-4 text-right flex justify-end space-x-2">
                                
                                <!-- ¡CAMBIO! Botón "Ver" ahora está fuera del IF y es para todos -->
                                <button class="view-order-btn flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 dark:hover:bg-blue-900/80 px-3 py-1 rounded-lg"
                                        title="Ver Detalle"
                                        data-id="<?php echo e($pedido['id_pedido']); ?>"
                                        data-cliente-id="<?php echo e($pedido['id_cliente']); ?>"
                                        data-cliente-nombre="<?php echo e(e($pedido['nombre_razon_social'])); ?>"
                                        data-fecha="<?php echo e($pedido['fecha_cotizacion']); ?>"
                                        data-direccion="<?php echo e(e($pedido['direccion_entrega'])); ?>"
                                        data-estado="<?php echo e(e($pedido['estado'])); ?>">
                                    <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Ver
                                </button>

                                <?php if ($pedido['estado'] === 'Pendiente' || $pedido['estado'] === 'En Preparacion'): ?>
                                    <!-- ¡MODIFICADO! Formularios ahora con JS (clases) -->
                                    <form action="api/pedidos_actions.php" method="POST" class="inline deliver-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="deliver">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="flex items-center text-sm font-medium text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 dark:hover:bg-green-900/80 px-3 py-1 rounded-lg" title="Marcar Entregado">
                                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i> Entregar
                                        </button>
                                    </form>
                                   
                                    <form action="api/pedidos_actions.php" method="POST" class="inline cancel-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="flex items-center text-sm font-medium text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/80 px-3 py-1 rounded-lg" title="Cancelar Pedido">
                                            <i data-lucide="x-circle" class="w-4 h-4 mr-1"></i> Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <!-- ¡NUEVO! Colspan actualizado -->
                    <tr><td colspan="8" class="px-6 py-4 text-center">No hay pedidos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA NUEVO/EDITAR PEDIDO -->
<div id="order-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Crear Nuevo Pedido</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
       
        <form id="order-form" class="flex-grow flex flex-col overflow-hidden">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id_pedido" id="form-order-id" value="">
            <input type="hidden" name="detalle_pedido" id="detalle_pedido_json">

            <!-- ¡NUEVO! Contenedor de mensajes del modal -->
            <div id="modal-message-container"></div>
            
            <!-- ¡NUEVO! Contenedor para el estado del pedido (modo Ver) -->
            <div id="view-mode-status" class="hidden"></div>


            <!-- SECCIÓN DATOS GENERALES -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="id_cliente" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                    <select id="id_cliente" name="id_cliente" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></select>
                </div>
                <div>
                    <label for="fecha_cotizacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                    <input type="date" id="fecha_cotizacion" name="fecha_cotizacion" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div class="md:col-span-2">
                    <label for="direccion_entrega" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección de Entrega</LAbel>
                    <input type="text" id="direccion_entrega" name="direccion_entrega" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>

            <!-- SECCIÓN AGREGAR PRODUCTOS (Solo para 'crear') -->
            <div id="add-item-section">
                <div class="border dark:border-gray-700 rounded-lg p-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-4">
                        <label for="producto_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Producto</label>
                        <select id="producto_select" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg"></select>
                    </div>
                    <div class="md:col-span-3">
                        <label for="cantidad" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad</label>
                        <input type="number" id="cantidad" min="1" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label for="precio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio Unit.</label>
                        <input type="number" id="precio" min="0.01" step="0.01" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg">
                    </div>
                    <div class="md:col-span-3 flex">
                        <button type="button" id="add-item-btn" class="w-full flex items-center justify-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-transform duration-300 hover:scale-105">
                            <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i> Agregar
                        </button>
                    </div>
                </div>
            </div>
           
            <!-- SECCIÓN DETALLE DEL PEDIDO -->
            <div class="flex-grow overflow-y-auto mt-4">
                <table class="w-full text-sm">
                <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-white">
                        <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase">Producto</th>
                            <th class="px-4 py-2 text-xs font-semibold uppercase">Cantidad</th>
                            <th class="px-4 py-2 text-xs font-semibold uppercase">Precio Unit.</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase">Subtotal</th>
                            </tr>
                    </thead>
                    <tbody id="detalle-pedido-body">
                        <!-- Filas de productos se agregarán aquí con JS -->
                    </tbody>
                </table>
            </div>

             <!-- SECCIÓN TOTALES Y ACCIONES -->
            <div class="mt-6 pt-4 border-t dark:border-gray-700 flex justify-between items-center">
                <div>
                    <span class="text-lg font-bold text-gray-800 dark:text-gray-100">TOTAL:</span>
                    <span id="total-pedido" class="text-2xl font-bold ml-2 text-gray-900 dark:text-white">$ 0.00</span>
                </div>
                <div class="flex space-x-4">
                    <button type="button" id="cancel-btn" class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">Cancelar</button>
                    <button type="submit" id="submit-btn" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Crear Pedido</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>