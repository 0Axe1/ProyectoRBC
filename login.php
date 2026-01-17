<?php
// Iniciar la sesión aquí también es una buena práctica
session_start();

// Si el usuario ya está logueado, redirigirlo al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

// --- ¡NUEVO! GENERACIÓN DE TOKEN CSRF ---
// Se genera un token único por sesión para proteger el formulario de login.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
// --- FIN DE GENERACIÓN DE TOKEN CSRF ---


// Lógica para el formulario
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 1) {
        $error_message = 'Usuario o contraseña incorrectos.';
    } elseif ($_GET['error'] == 'csrf') {
        $error_message = 'Error de seguridad. Por favor, intente de nuevo.';
    } elseif ($_GET['error'] == 'db') {
         $error_message = 'Error de conexión. Intente más tarde.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema RBC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide-icons@0.378.0/dist/lucide.min.js"></script>
    <style>
        /* Puedes añadir aquí estilos específicos si es necesario, pero Tailwind es suficiente */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center h-screen">

    <div class="w-full max-w-md p-8 space-y-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg">
        <div class="flex flex-col items-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Importadora RBC</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Inicia sesión en tu cuenta</p>
        </div>

        <form class="mt-8 space-y-6" action="includes/validate_login.php" method="POST">
            
            <!-- ¡NUEVO! Campo oculto para el token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <?php if (!empty($error_message)): ?>
                <div class="p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm" role="alert">
                    <!-- 
                        CORRECCIÓN (Línea 63):
                        La función `e()` no existe en PHP puro. Se reemplaza por la función nativa 
                        `htmlspecialchars()` para mostrar el texto de forma segura y evitar errores.
                    -->
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Usuario</label>
                <input id="username" name="username" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                <input id="password" name="password" type="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                    Iniciar Sesión
                </button>
            </div>
        </form>
        </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>