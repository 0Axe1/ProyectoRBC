<?php
// ... (toda la lógica PHP de mensajes y consultas) ...
$mensaje = '';
if (isset($_GET['success'])) {
    $codigos_exito = [
        '1' => 'Pedido creado exitosamente. El inventario ha sido actualizado.',
        '2' => 'Pedido cancelado exitosamente. El inventario ha sido restaurado.',
        '3' => 'Pedido marcado como Entregado. Se ha generado el registro de venta correspondiente.',
    ];
    $mensaje = "<div class='mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . e($codigos_exito[$_GET['success']]) . "</div>";
}
if (isset($_GET['error'])) {
    $codigos_error = [
        '1' => 'Datos incompletos. Por favor, seleccione un cliente y agregue al menos un producto.',
        '2' => 'Error al procesar el pedido. Es posible que no haya stock suficiente para un producto.',
        '3' => 'El ID del pedido es inválido.',
        '4' => 'Error al cancelar el pedido.',
        '5' => 'Error al registrar la entrega del pedido. La operación fue cancelada.',
        'csrf' => 'Error de seguridad al procesar la solicitud. Por favor, recargue la página e intente de nuevo.',
        'permiso' => 'No tiene autorización para realizar esta acción.',
    ];
    $mensaje = "<div class='mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg'>" . e($codigos_error[$_GET['error']] ?? 'Hubo un error al realizar la operación.') . "</div>";
}

$sql = "SELECT
            ped.id_pedido,
            cli.nombre_razon_social,
            ped.fecha_cotizacion,
            est.nombre_estado AS estado, -- Se obtiene el nombre del estado
            (SELECT SUM(precio_total_cotizado)
             FROM detalle_de_pedido
             WHERE id_pedido = ped.id_pedido) as total_pedido
        FROM pedidos ped
        JOIN clientes cli ON ped.id_cliente = cli.id_cliente
        JOIN estados_pedido est ON ped.id_estado_pedido = est.id_estado_pedido -- Se une para obtener el nombre
        ORDER BY ped.fecha_cotizacion DESC, ped.id_pedido DESC";
$stmt = $pdo->query($sql);
?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md fade-in">
    <div id="message-container"><?php echo $mensaje; // $mensaje ya está escapado ?></div>

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
                    <th scope="col" class="px-6 py-3">ID Pedido</th>
                    <th scope="col" class="px-6 py-3">Cliente</th>
                    <th scope="col" class="px-6 py-3">Fecha</th>
                    <th scope="col" class="px-6 py-3">Total</th>
                    <th scope="col" class="px-6 py-3">Estado</th>
                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stmt && $stmt->rowCount() > 0) : ?>
                    <?php while($pedido = $stmt->fetch(PDO::FETCH_ASSOC)):
                        // Mapeo de clases CSS basado en el nombre del estado
                        $estado_classes = [
                            'Pendiente' => 'bg-yellow-200 text-yellow-800',
                            'Entregado' => 'bg-green-200 text-green-800',
                            'Cancelado' => 'bg-red-200 text-red-800',
                            'En Preparacion' => 'bg-blue-200 text-blue-800',
                        ];
                        $estado_class = $estado_classes[$pedido['estado']] ?? 'bg-gray-200 text-gray-800';
                    ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-bold">#<?php echo e($pedido['id_pedido']); ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($pedido['nombre_razon_social']); ?></td>
                            <td class="px-6 py-4"><?php echo date("d/m/Y", strtotime($pedido['fecha_cotizacion'])); ?></td>
                            <td class="px-6 py-4">$ <?php echo number_format($pedido['total_pedido'] ?? 0, 2); ?></td>
                            <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo e($estado_class); ?>"><?php echo e($pedido['estado']); ?></span></td>
                            <td class="px-6 py-4 text-right">
                                <?php if ($pedido['estado'] === 'Pendiente' || $pedido['estado'] === 'En Preparacion'): ?>
                                   
                                    <form action="api/pedidos_actions.php" method="POST" class="inline mr-4" onsubmit="return confirm('¿Confirmar que este pedido ha sido entregado? Esta acción creará un registro de venta.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="deliver">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="font-medium text-green-600 dark:text-green-500 hover:underline">
                                            Marcar Entregado
                                        </button>
                                    </form>
                                   
                                    <form action="api/pedidos_actions.php" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres cancelar este pedido? Esta acción repondrá el stock.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?php echo e($pedido['id_pedido']); ?>">
                                        <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">
                                            Cancelar
                                        </button>
                                    </form>
                                   
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center">No hay pedidos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- MODAL PARA NUEVO PEDIDO -->
<div id="order-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100">Crear Nuevo Pedido</h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
       
        <form id="order-form" action="api/pedidos_actions.php" method="POST" class="flex-grow flex flex-col overflow-hidden">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="detalle_pedido" id="detalle_pedido_json">

            <!-- SECCIÓN DATOS GENERALES -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="id_cliente" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                    <select id="id_cliente" name="id_cliente" required class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-lg"></select>
                </div>
                <div>
                    <label for="fecha_cotizacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                    <input type="date" id="fecha_cotizacion" name="fecha_cotizacion" required class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label for="direccion_entrega" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dirección de Entrega</LAbel>
                    <input type="text" id="direccion_entrega" name="direccion_entrega" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-lg">
                </div>
            </div>

            <!-- SECCIÓN AGREGAR PRODUCTOS -->
            <div class="border dark:border-gray-700 rounded-lg p-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label for="producto_select" class="block text-sm font-medium">Producto</label>
                    <select id="producto_select" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg"></select>
                </div>
                <div class="md:col-span-3">
                    <label for="cantidad" class="block text-sm font-medium">Cantidad</label>
                    <input type="number" id="cantidad" min="1" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                </div>
                 <div class="md:col-span-2">
                    <label for="precio" class="block text-sm font-medium">Precio Unit.</label>
                    <input type="number" id="precio" min="0.01" step="0.01" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                </div>
                <div class="md:col-span-3 flex">
                    <button type="button" id="add-item-btn" class="w-full flex items-center justify-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i> Agregar al Pedido
                    </button>
                </div>
            </div>
           
            <!-- SECCIÓN DETALLE DEL PEDIDO -->
            <div class="flex-grow overflow-y-auto mt-4">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2">Cantidad</th>
                            <th class="px-4 py-2">Precio Unit.</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                            <th class="px-4 py-2"></th>
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
                    <span class="text-lg font-bold">TOTAL:</span>
                    <span id="total-pedido" class="text-2xl font-bold ml-2">$ 0.00</span>
                </div>
                <div class="flex space-x-4">
                    <button type="button" id="cancel-btn" class="px-6 py-2 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200">Cancelar</button>
                    <button type="submit" id="submit-btn" class="px-6 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700">Crear Pedido</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ¡CAMBIO! SCRIPT ELIMINADO -->
