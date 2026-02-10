<?php
// pages/usuarios.php
$mensaje = '';
if (isset($_GET['success'])) {
    $codigos_exito = [
        '1' => 'Usuario creado exitosamente.',
        '2' => 'Usuario actualizado exitosamente.',
        '3' => 'Usuario eliminado exitosamente.',
    ];
    $mensaje = "<div class='mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg'>" . e($codigos_exito[$_GET['success']]) . "</div>";
}
if (isset($_GET['error'])) {
    $codigos_error = [
        '1' => 'Todos los campos marcados con * son requeridos.',
        '2' => 'Error al procesar la solicitud en la base de datos.',
        '3' => 'No puedes eliminar tu propio usuario.',
        'csrf' => 'Error de seguridad al procesar la solicitud. Por favor, recargue la página e intente de nuevo.',
        'permiso' => 'No tiene autorización para realizar esta acción.',
    ];
    $error_msg = $codigos_error[$_GET['error']] ?? $codigos_error['2'];
    $mensaje = "<div class='mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg'>" . e($error_msg) . "</div>";
}

$sql_usuarios = "SELECT
                    u.id_usuario,
                    u.nombre_usuario,
                    r.id_rol,
                    r.nombre_rol,
                    (SELECT GROUP_CONCAT(p.nombre_permiso SEPARATOR ', ')
                     FROM roles_permisos rp
                     JOIN permisos p ON rp.id_permiso = p.id_permiso
                     WHERE rp.id_rol = u.id_rol) as permisos_lista
                 FROM usuarios u
                 JOIN roles r ON u.id_rol = r.id_rol
                 WHERE u.activo = 1
                 ORDER BY u.nombre_usuario ASC";
$resultado = $pdo->query($sql_usuarios);

$sql_roles = "SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol ASC";
$roles_result = $pdo->query($sql_roles);
$roles = $roles_result->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg fade-in">
    <div id="message-container"><?php echo $mensaje; ?></div>
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Gestión de Usuarios</h3>
        <button id="add-user-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
            <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Agregar Usuario
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3">Nombre de Usuario</th>
                    <th class="px-6 py-3">Rol</th>
                    <th class="px-6 py-3">Permisos (Heredados del Rol)</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->rowCount() > 0) : ?>
                    <?php while($usuario = $resultado->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($usuario['nombre_usuario']); ?></td>
                            <td class="px-6 py-4"><?php echo e($usuario['nombre_rol']); ?></td>
                            <td class="px-6 py-4 text-xs italic"><?php echo e($usuario['permisos_lista'] ?? 'Sin permisos asignados al rol'); ?></td>
                            <td class="px-6 py-4 flex space-x-2 justify-end">
                                <button class="edit-btn flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/50 dark:text-blue-400 dark:hover:bg-blue-900/80 px-3 py-1 rounded-lg"
                                    data-id="<?php echo e($usuario['id_usuario']); ?>"
                                    data-nombre="<?php echo e($usuario['nombre_usuario']); ?>"
                                    data-id-rol="<?php echo e($usuario['id_rol']); ?>">
                                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Editar
                                </button>
                                
                                <form action="api/usuarios_actions.php" method="POST" class="inline delete-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($usuario['id_usuario']); ?>">
                                    <button type="submit" class="flex items-center text-sm font-medium text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/80 px-3 py-1 rounded-lg">
                                        <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i> Deshabilitar
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
                                    <i data-lucide="users" class="w-10 h-10 text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">No hay usuarios registrados.</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Comience agregando un nuevo usuario.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA USUARIOS -->
<div id="user-modal" class="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/80 backdrop-blur-sm flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-lg">
        <div class="flex justify-between items-center mb-6">
            <h4 id="modal-title" class="text-2xl font-bold text-gray-800 dark:text-gray-100"></h4>
            <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        <form id="user-form" action="api/usuarios_actions.php" method="POST">
            <div id="modal-message-container"></div>
           
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" id="form-action">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <div class="space-y-4">
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de Usuario *</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required minlength="3" maxlength="50" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label for="id_rol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rol *</label>
                    <select id="id_rol" name="id_rol" required class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">-- Seleccione un rol --</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo e($rol['id_rol']); ?>">
                                <?php echo e($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="contrasena" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Contraseña <span id="password-required-mark" class="hidden">*</span>
                    </label>
                    <input type="password" id="contrasena" name="contrasena" minlength="6" class="w-full px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <small id="password-help" class="text-xs text-gray-500 dark:text-gray-400">Dejar en blanco para no cambiar la contraseña existente.</small>
                </div>
            </div>
            <div class="flex justify-end space-x-4 mt-8 pt-4 border-t dark:border-gray-700">
                <button type="button" id="cancel-btn" class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">Cancelar</button>
                <button type="submit" id="submit-btn" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"></button>
            </div>
        </form>
    </div>
</div>
