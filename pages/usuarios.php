<?php
// ... (toda la lógica PHP de mensajes y consultas) ...
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
                 ORDER BY u.nombre_usuario ASC";
$resultado = $pdo->query($sql_usuarios);

$sql_roles = "SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol ASC";
$roles_result = $pdo->query($sql_roles);
$roles = $roles_result->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md fade-in">
    <?php echo $mensaje; // $mensaje ya está escapado ?>
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Gestión de Usuarios</h3>
        <button id="add-user-btn" class="flex items-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
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
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e($usuario['nombre_usuario']); ?></td>
                            <td class="px-6 py-4"><?php echo e($usuario['nombre_rol']); ?></td>
                            <td class="px-6 py-4 text-xs italic"><?php echo e($usuario['permisos_lista'] ?? 'Sin permisos asignados al rol'); ?></td>
                            <td class="px-6 py-4 text-right">
                                <button class="edit-btn font-medium text-blue-600 dark:text-blue-500 hover:underline mr-4"
                                    data-id="<?php echo e($usuario['id_usuario']); ?>"
                                    data-nombre="<?php echo e($usuario['nombre_usuario']); ?>"
                                    data-id-rol="<?php echo e($usuario['id_rol']); ?>">
                                    Editar
                                </button>
                               
                                <form action="api/usuarios_actions.php" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a este usuario?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo e($usuario['id_usuario']); ?>">
                                    <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA USUARIOS (MIGRADO) -->
<div id="user-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-30">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-8 w-full max-w-lg">
        <form id="user-form" action="api/usuarios_actions.php" method="POST">
            <h4 id="modal-title" class="text-2xl font-bold mb-6"></h4>
           
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
           
            <input type="hidden" name="action" id="form-action">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <div class="space-y-4">
                <div>
                    <label for="nombre_usuario" class="block mb-1">Nombre de Usuario *</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                </div>
                <div>
                    <label for="id_rol" class="block mb-1">Rol *</label>
                    <select id="id_rol" name="id_rol" required class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <option value="">-- Seleccione un rol --</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo e($rol['id_rol']); ?>">
                                <?php echo e($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="contrasena" class="block mb-1">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <small id="password-help" class="text-xs text-gray-500">Dejar en blanco para no cambiar la contraseña existente.</small>
                </div>
            </div>
            <div class="flex justify-end space-x-4 mt-8">
                <button type="button" id="cancel-btn" class="px-6 py-2 bg-gray-200 dark:bg-gray-600 rounded-lg">Cancelar</button>
                <button type="submit" id="submit-btn" class="px-6 py-2 text-white bg-green-600 rounded-lg"></button>
            </div>
        </form>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<!-- ¡CAMBIO! SCRIPT ELIMINADO -->
