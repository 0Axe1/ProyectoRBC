<?php
    // 1. Iniciar la sesión PRIMERO
    session_start();

    // 2. Incluir la conexión y las funciones (como e())
    require_once __DIR__ . '/config/db_connection.php';

    // 3. ¡NUEVO! Conectar a la BD UNA SOLA VEZ
    try {
        $pdo = conectarDB(); // $pdo estará disponible para todas las 'pages'
    } catch (Exception $e) {
        // Error fatal de BD
        // Se usa la función e() para escapar el mensaje antes de mostrarlo
        die("Error de conexión a la Base de Datos: " . e($e->getMessage())); 
    }

    // 4. Ahora incluir el header, que usa la sesión y la función e()
    include 'partials/header.php';

    // --- LÓGICA DE AUTORIZACIÓN ---
    
    // Se obtiene la página solicitada de la URL, con 'dashboard' como valor predeterminado.
    $paginaActual = $_GET['page'] ?? 'dashboard';

    // 1. Mapeo de páginas a los permisos requeridos (debe coincidir con la BD)
    $pagina_permiso_map = [
        'dashboard'  => 'ver_dashboard',
        'clientes'   => 'ver_clientes',
        'pedidos'    => 'ver_pedidos',
        'inventario' => 'ver_inventario',
        'categorias' => 'ver_categorias', // <-- AÑADIDO: Mapeo del permiso de categorías
        'reportes'   => 'ver_reportes',
        'usuarios'   => 'ver_usuarios'
    ];

    // 2. Obtener el permiso requerido para la página actual
    $permisoRequerido = $pagina_permiso_map[$paginaActual] ?? null;

    // 3. Verificar si el usuario tiene el permiso
    $permisos_del_usuario = $_SESSION['permisos'] ?? [];
    if ($permisoRequerido !== null) {
        if (!in_array($permisoRequerido, $permisos_del_usuario)) {
            // ACCESO DENEGADO (Redirige a dashboard)
            $paginaActual = 'dashboard';
        }
    }
    
    // --- FIN DE LA LÓGICA DE AUTORIZACIÓN ---

    // Una 'lista blanca' de todas las páginas válidas
    $paginasPermitidas = ['dashboard', 'clientes', 'pedidos', 'inventario', 'categorias', 'reportes', 'usuarios']; // <-- AÑADIDO 'categorias' aquí
    
    // Si la página actual no está en la lista blanca (aunque el usuario tenga permiso), 
    // se le redirige al dashboard.
    if (!in_array($paginaActual, $paginasPermitidas)) {
        $paginaActual = 'dashboard';
    }
?>
<main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900/50">
    <header class="bg-white dark:bg-gray-800 p-6 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <h2 id="page-title" class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
            <?php echo e(ucfirst($paginaActual)); ?>
        </h2>
    </header>
    <div class="p-8">
        <?php
            // Incluye el contenido de la página solicitada
            // Esta página ahora tiene acceso a la variable $pdo
            // El archivo debe existir en 'pages/categorias.php'
            include 'pages/' . $paginaActual . '.php';
        ?>
    </div>
</main>
<?php
    // --- SECCIÓN DE SCRIPTS ---

    // 1. Cargar Lucide (íconos) SIEMPRE
    echo '<script src="https://unpkg.com/lucide@latest"></script>';

    // 2. Cargar el JS específico de la página si existe
    $js_file_path = 'js/pages/' . $paginaActual . '.js';
    if (file_exists($js_file_path)) {
        // Se carga DESPUÉS de lucide
        echo '<script src="' . e($js_file_path) . '"></script>';
    }
    // --- FIN DE LA SECCIÓN DE SCRIPTS ---

    // Incluye el pie de página
    include 'partials/footer.php';
    
    // ¡NUEVO! Cerrar la conexión PDO al final
    $pdo = null;
?>