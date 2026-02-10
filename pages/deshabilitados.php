<?php
// pages/deshabilitados.php

// 1. CONTROL DE ACCESO
// Solo el rol de Administrador (ID 1) tiene acceso a la papelera
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo '<div class="p-8 text-center">
            <div class="text-red-500 mb-4">
                <i data-lucide="shield-alert" class="w-16 h-16 mx-auto"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Acceso Denegado</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Solo los administradores pueden acceder a la papelera de reciclaje.</p>
            <a href="index.php?page=dashboard" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                Volver al Dashboard
            </a>
          </div>';
    return; // Detener la ejecución del resto del archivo
}

// (El control general ya se hace en index.php, pero aquí podemos refinar si es necesario)

// 2. LÓGICA DE REACTIVACIÓN (POST)
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reactivar') {
    $tipo_reactivacion = $_POST['tipo'] ?? '';
    $id_reactivacion = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);

    if ($id_reactivacion) {
        try {
            switch ($tipo_reactivacion) {
                case 'clientes':
                    $sql = "UPDATE clientes SET estado = 1 WHERE id_cliente = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id_reactivacion]);
                    $mensaje = "Cliente reactivado correctamente.";
                    $tipo_mensaje = "success";
                    break;
                case 'usuarios':
                    $sql = "UPDATE usuarios SET activo = 1 WHERE id_usuario = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id_reactivacion]);
                    $mensaje = "Usuario reactivado correctamente.";
                    $tipo_mensaje = "success";
                    break;
                case 'productos':
                    $sql = "UPDATE productos SET activo = 1 WHERE id_producto = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id_reactivacion]);
                    $mensaje = "Producto reactivado correctamente.";
                    $tipo_mensaje = "success";
                    break;
                case 'pedidos':
                    // Restaurar pedido cancelado (3) a pendiente (1)
                    // Verificar primero si está cancelado
                    $checkSql = "SELECT id_estado_pedido FROM pedidos WHERE id_pedido = ?";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->execute([$id_reactivacion]);
                    $estadoActual = $checkStmt->fetchColumn();

                    if ($estadoActual == 3) {
                        $sql = "UPDATE pedidos SET id_estado_pedido = 1 WHERE id_pedido = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$id_reactivacion]);
                        $mensaje = "Pedido restaurado a estado 'Pendiente'.";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "El pedido no está en estado Cancelado.";
                        $tipo_mensaje = "error";
                    }
                    break;
                default:
                    $mensaje = "Tipo de reactivación no válido.";
                    $tipo_mensaje = "error";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al reactivar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// 3. OBTENCIÓN DE DATOS (GET)
$tab = $_GET['tab'] ?? 'clientes'; // clientes, productos, pedidos
$search = trim($_GET['search'] ?? '');

$resultados = [];

try {
    if ($tab === 'clientes') {
        $sql = "SELECT id_cliente, nombre_razon_social, nit_ruc, ubicacion, fecha_actualizacion 
                FROM clientes 
                WHERE estado = 0";
        $params = [];
        if ($search) {
            $sql .= " AND (nombre_razon_social LIKE ? OR nit_ruc LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY fecha_actualizacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab === 'usuarios') {
        $sql = "SELECT u.id_usuario, u.nombre_usuario, u.fecha_creacion, r.nombre_rol
                FROM usuarios u
                JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.activo = 0";
        $params = [];
        if ($search) {
            $sql .= " AND (nombre_usuario LIKE ?)";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY fecha_creacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab === 'productos') {
        $sql = "SELECT p.id_producto, p.nombre_producto, p.precio, p.stock, p.fecha_actualizacion
                FROM productos p
                WHERE p.activo = 0";
        $params = [];
         if ($search) {
            $sql .= " AND (p.nombre_producto LIKE ?)";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY p.fecha_actualizacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($tab === 'pedidos') {
        $sql = "SELECT p.id_pedido, c.nombre_razon_social, p.fecha_cotizacion, p.fecha_actualizacion, v.total_venta
                FROM pedidos p
                JOIN clientes c ON p.id_cliente = c.id_cliente
                LEFT JOIN ventas v ON p.id_pedido = v.id_pedido
                WHERE p.id_estado_pedido = 3"; // 3 = Cancelado
        $params = [];
        if ($search) {
             $sql .= " AND (c.nombre_razon_social LIKE ? OR p.id_pedido = ?)";
            $params[] = "%$search%";
            $params[] = $search; // Busqueda exacta por ID si es numero
        }
        $sql .= " ORDER BY p.fecha_actualizacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    echo '<div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">Error de base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo "<script>document.getElementById('page-title').textContent = 'Papelera / Deshabilitados';</script>";
?>

<div class="space-y-6 fade-in">
    
    <!-- Mensajes de Feedback -->
    <?php if ($mensaje): ?>
        <div class="p-4 mb-4 rounded-xl <?php echo $tipo_mensaje === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?> flex items-center justify-between">
            <div class="flex items-center">
                <i data-lucide="<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 mr-2"></i>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-500 hover:text-gray-700"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
    <?php endif; ?>

    <!-- Pestañas de Navegación -->
    <div class="flex space-x-1 bg-gray-200 dark:bg-gray-700 p-1 rounded-xl w-fit">
        <a href="index.php?page=deshabilitados&tab=clientes" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center <?php echo $tab === 'clientes' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            <i data-lucide="users" class="w-4 h-4 mr-2"></i> Clientes
        </a>
        <a href="index.php?page=deshabilitados&tab=usuarios" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center <?php echo $tab === 'usuarios' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            <i data-lucide="user-x" class="w-4 h-4 mr-2"></i> Usuarios
        </a>
        <a href="index.php?page=deshabilitados&tab=productos" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center <?php echo $tab === 'productos' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            <i data-lucide="package" class="w-4 h-4 mr-2"></i> Productos
        </a>
        <a href="index.php?page=deshabilitados&tab=pedidos" 
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center <?php echo $tab === 'pedidos' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
            <i data-lucide="shopping-bag" class="w-4 h-4 mr-2"></i> Pedidos (Cancelados)
        </a>
    </div>

    <!-- Buscador y Filtros -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
        <form action="index.php" method="GET" class="w-full sm:w-96 relative">
            <input type="hidden" name="page" value="deshabilitados">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Buscar en <?php echo ucfirst($tab); ?>..." 
                   class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all dark:text-gray-100 text-sm">
            <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        </form>
        
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Mostrando <?php echo count($resultados); ?> registros eliminados/inactivos
        </div>
    </div>

    <!-- Listado de Resultados -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <?php if (empty($resultados)): ?>
            <div class="p-12 text-center text-gray-500 dark:text-gray-400 flex flex-col items-center">
                <i data-lucide="trash-2" class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600"></i>
                <p class="text-lg font-medium">No hay items deshabilitados aquí</p>
                <p class="text-sm">Todo parece estar en orden en esta sección.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 uppercase text-xs font-bold border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <?php if ($tab === 'clientes'): ?>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">NIT/RUC</th>
                                <th class="px-6 py-4">Ubicación</th>
                                <th class="px-6 py-4">Deshabilitado El</th>
                                <th class="px-6 py-4 text-right">Acción</th>
                            <?php elseif ($tab === 'usuarios'): ?>
                                <th class="px-6 py-4">Usuario</th>
                                <th class="px-6 py-4">Rol</th>
                                <th class="px-6 py-4">Creado El</th>
                                <th class="px-6 py-4 text-right">Acción</th>
                            <?php elseif ($tab === 'productos'): ?>
                                <th class="px-6 py-4">Producto</th>

                                <th class="px-6 py-4">Precio Act.</th>
                                <th class="px-6 py-4">Stock</th>
                                <th class="px-6 py-4 text-right">Acción</th>
                            <?php elseif ($tab === 'pedidos'): ?>
                                <th class="px-6 py-4">Pedido #</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Monto</th>
                                <th class="px-6 py-4">Fecha Cotización</th>
                                <th class="px-6 py-4">Cancelado El</th>
                                <th class="px-6 py-4 text-right">Acción</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php foreach ($resultados as $row): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                <?php if ($tab === 'clientes'): ?>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($row['nombre_razon_social']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($row['nit_ruc']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($row['ubicacion']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">
                                        <?php echo htmlspecialchars($row['fecha_actualizacion']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" onsubmit="return confirm('¿Estás seguro de reactivar este cliente?');">
                                            <input type="hidden" name="action" value="reactivar">
                                            <input type="hidden" name="tipo" value="clientes">
                                            <input type="hidden" name="id" value="<?php echo $row['id_cliente']; ?>">
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center ml-auto">
                                                <i data-lucide="refresh-ccw" class="w-3.5 h-3.5 mr-1.5"></i> Activar
                                            </button>
                                        </form>
                                    </td>

                                <?php elseif ($tab === 'usuarios'): ?>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($row['nombre_usuario']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                        <?php 
                                            // Quick role lookup or join in query would be better, but for now:
                                            // We selected id_rol, let's just show it or we can add a join in the query above.
                                            // Let's improve the query above to join roles.
                                            // Actually I will just show id_rol for now or quickly adding join in "SELECT ... JOIN roles ..."
                                            // Wait, I can easily fix the query. Let's assume I fixed the query in the previous chunk.
                                            // I added "id_rol" to SELECT, but not the name. Let me update the previous chunk.
                                            // Actually, simplest is to just show ID or generic text, OR better, I will update the query in the previous chunk to join roles.
                                            // Retrying the chunk logic.
                                            // Ideally I should update the query chunk to include role name. 
                                            // Let's assume I'll come back to fix the query if I missed it? 
                                            // No, I can't look back. I will include a better query in the Plan? 
                                            // No, I am in execution. I will use a simple query for now or try to fetch role name.
                                            // Let's just use the id_rol for now to be safe or try to do a JOIN. 
                                            // I'll update the query in the previous chunk to:
                                            // SELECT u.id_usuario, u.nombre_usuario, u.fecha_creacion, r.nombre_rol 
                                            // FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol ...
                                            // But I already submitted the previous chunk? No, I am constructing the chunks now.
                                            // Ah, I can edit the previous chunk before submitting!
                                            // Okay, I will edit the previous chunk in this same tool call! 
                                            // Wait, I cannot edit "previous chunk". I am defining them all now.
                                            // So I will make sure the query in the 2nd chunk has the JOIN.
                                            
                                            echo htmlspecialchars($row['nombre_rol'] ?? $row['id_rol']); 
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">
                                        <?php echo htmlspecialchars($row['fecha_creacion']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" onsubmit="return confirm('¿Estás seguro de reactivar este usuario?');">
                                            <input type="hidden" name="action" value="reactivar">
                                            <input type="hidden" name="tipo" value="usuarios">
                                            <input type="hidden" name="id" value="<?php echo $row['id_usuario']; ?>">
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center ml-auto">
                                                <i data-lucide="refresh-ccw" class="w-3.5 h-3.5 mr-1.5"></i> Activar
                                            </button>
                                        </form>
                                    </td>

                                <?php elseif ($tab === 'productos'): ?>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($row['nombre_producto']); ?>
                                    </td>

                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                        Bs. <?php echo number_format($row['precio'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold <?php echo $row['stock'] > 0 ? 'text-gray-700 dark:text-gray-300' : 'text-red-500'; ?>">
                                            <?php echo $row['stock']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" onsubmit="return confirm('¿Estás seguro de reactivar este producto?');">
                                            <input type="hidden" name="action" value="reactivar">
                                            <input type="hidden" name="tipo" value="productos">
                                            <input type="hidden" name="id" value="<?php echo $row['id_producto']; ?>">
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center ml-auto">
                                                <i data-lucide="refresh-ccw" class="w-3.5 h-3.5 mr-1.5"></i> Activar
                                            </button>
                                        </form>
                                    </td>

                                <?php elseif ($tab === 'pedidos'): ?>
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-gray-100">
                                        #<?php echo htmlspecialchars($row['id_pedido']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($row['nombre_razon_social']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium">
                                        <?php echo $row['total_venta'] ? 'Bs. '.number_format($row['total_venta'], 2) : '---'; ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">
                                        <?php echo htmlspecialchars($row['fecha_cotizacion']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">
                                        <?php echo htmlspecialchars($row['fecha_actualizacion']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" onsubmit="return confirm('¿Deseas restaurar este pedido a estado PENDIENTE?');">
                                            <input type="hidden" name="action" value="reactivar">
                                            <input type="hidden" name="tipo" value="pedidos">
                                            <input type="hidden" name="id" value="<?php echo $row['id_pedido']; ?>">
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center ml-auto">
                                                <i data-lucide="history" class="w-3.5 h-3.5 mr-1.5"></i> Restaurar
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
