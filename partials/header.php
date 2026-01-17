<?php
// ¡CORREGIDO!
// Se eliminó session_start();
// index.php ya inicia la sesión.
// La variable $pdo ya está disponible desde index.php

// 1. VERIFICACIÓN DE SESIÓN ACTIVA
// Si el usuario no ha iniciado sesión, se le redirige al login.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- ¡NUEVO! GENERACIÓN DE TOKEN CSRF ---
// Se genera un token único por sesión para proteger contra ataques CSRF.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Hacemos el token disponible en una variable para los formularios
$csrf_token = $_SESSION['csrf_token'];
// --- FIN DE GENERACIÓN DE TOKEN CSRF ---

$paginaActual = $_GET['page'] ?? 'dashboard';

// --- ¡NUEVO! Cargar permisos para comprobaciones ---
// Es una buena práctica tener los permisos en una variable fácil de usar
$permisos_usuario = $_SESSION['permisos'] ?? [];
?>
<!DOCTYPE html>
<html lang="es" class=""> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title><?php echo htmlspecialchars(ucfirst($paginaActual)); ?> - Sistema RBC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- ¡CORREGIDO! Se eliminó la carga duplicada de lucide.min.js de aquí -->
    
    <link rel="stylesheet" href="css/style.css">
    <style>
        .active {
            @apply text-white bg-indigo-600 dark:bg-indigo-700 shadow-md;
        }
        .inactive {
            @apply text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
<div id="app" class="flex flex-col h-screen">
    <div class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-20">
        <div class="h-16 flex items-center justify-center bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">Importadora RBC</h1>
        </div>
        <header class="p-3">
            <div class="flex items-center justify-between">
                <nav class="flex items-center space-x-1 overflow-x-auto pb-1">
                
                    <!-- 
                        --- CORRECCIÓN DE LÓGICA DE ROLES ---
                        Cada enlace se muestra SÓLO SI el usuario tiene el permiso
                        correspondiente en su sesión.
                    -->

                    <?php if (in_array('ver_dashboard', $permisos_usuario)): ?>
                    <a href="index.php?page=dashboard" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'dashboard') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 mr-2"></i> Dashboard
                    </a>
                    <?php endif; ?>

                    <?php if (in_array('ver_clientes', $permisos_usuario)): ?>
                    <a href="index.php?page=clientes" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'clientes') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="users" class="w-4 h-4 mr-2"></i> Clientes
                    </a>
                    <?php endif; ?>

                    <?php if (in_array('ver_pedidos', $permisos_usuario)): ?>
                    <a href="index.php?page=pedidos" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'pedidos') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="shopping-cart" class="w-4 h-4 mr-2"></i> Pedidos
                    </a>
                    <?php endif; ?>

                    <?php if (in_array('ver_inventario', $permisos_usuario)): ?>
                    <a href="index.php?page=inventario" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'inventario') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="archive" class="w-4 h-4 mr-2"></i> Inventario
                    </a>
                    <?php endif; ?>

                    <!-- ¡NUEVO! ENLACE DE CATEGORÍAS AÑADIDO -->
                    <?php if (in_array('ver_categorias', $permisos_usuario)): ?>
                    <a href="index.php?page=categorias" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'categorias') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="layout-grid" class="w-4 h-4 mr-2"></i> Categorías
                    </a>
                    <?php endif; ?>
                    <!-- FIN DE LO NUEVO -->

                    <?php if (in_array('ver_reportes', $permisos_usuario)): ?>
                    <a href="index.php?page=reportes" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'reportes') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 mr-2"></i> Reportes
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('ver_usuarios', $permisos_usuario)): ?>
                    <a href="index.php?page=usuarios" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm <?php echo ($paginaActual == 'usuarios') ? 'active' : 'inactive'; ?>">
                        <i data-lucide="user-cog" class="w-4 h-4 mr-2"></i> Usuarios
                    </a>
                    <?php endif; ?>
                </nav>

                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i id="theme-toggle-dark-icon" data-lucide="moon" class="w-5 h-5 hidden"></i>
                        <i id="theme-toggle-light-icon" data-lucide="sun" class="w-5 h-5 hidden"></i>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://placehold.co/32x32/a0aec0/ffffff?text=U" alt="Avatar" class="w-8 h-8 rounded-full">
                        <span class="text-gray-600 dark:text-gray-300 text-sm hidden sm:inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?></span>
                    </div>
                    <a href="logout.php" class="nav-link flex items-center px-3 py-2 rounded-lg font-medium text-sm inactive" title="Cerrar Sesión">
                        <i data-lucide="log-out" class="w-4 h-4 mr-2"></i> Salir
                    </a>
                </div>
            </div>
        </header> 
    </div>