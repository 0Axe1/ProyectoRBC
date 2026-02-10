<?php
// pages/dashboard.php
// La conexión $pdo ya viene desde index.php, pero aquí ya no la usaremos para queries directas
// ya que todo se manejará vía AJAX para permitir el filtrado dinámico.
?>

<div class="fade-in space-y-8">
    
    <!-- HEADER & FILTROS -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Dashboard General</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Visión general del rendimiento del negocio</p>
        </div>

        <!-- Barra de Herramientas / Filtros -->
        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <!-- Icono Filtro -->
            <div class="pl-2">
                <i data-lucide="calendar" class="w-5 h-5 text-indigo-500"></i>
            </div>

            <!-- Selector Mes -->
            <select id="filter-month" class="bg-transparent border-none text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                <?php
                $meses = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];
                $currentMonth = date('n');
                foreach ($meses as $num => $nombre) {
                    $selected = ($num == $currentMonth) ? 'selected' : '';
                    echo "<option value='{$num}' {$selected}>{$nombre}</option>";
                }
                ?>
            </select>

            <span class="text-gray-300">|</span>

            <!-- Selector Año -->
            <select id="filter-year" class="bg-transparent border-none text-sm font-medium text-gray-700 dark:text-gray-200 focus:ring-0 cursor-pointer">
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                    echo "<option value='{$y}'>{$y}</option>";
                }
                ?>
            </select>

            <!-- Botón Actualizar (Opcional, ya que usaremos 'change' event) -->
            <button id="btn-refresh" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" title="Actualizar datos">
                <i data-lucide="refresh-cw" class="w-4 h-4 text-gray-500"></i>
            </button>
        </div>
    </div>

    <!-- 1. Encabezado Principal (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card: Ventas del Periodo -->
        <div class="relative p-6 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="trending-up" class="w-24 h-24 text-green-500"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider">Ventas</span>
                </div>
                <div class="mt-4">
                    <h3 id="kpi-ventas" class="text-3xl font-extrabold text-gray-900 dark:text-white">--</h3>
                    <p class="text-sm text-gray-500 mt-1">En el periodo seleccionado</p>
                </div>
            </div>
        </div>

        <!-- Card: Pedidos Pendientes -->
        <div class="relative p-6 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="clock" class="w-24 h-24 text-amber-500"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <i data-lucide="package" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Pendientes</span>
                </div>
                <div class="mt-4">
                    <h3 id="kpi-pedidos" class="text-3xl font-extrabold text-gray-900 dark:text-white">--</h3>
                    <p class="text-sm text-gray-500 mt-1">Pedidos por procesar</p>
                </div>
            </div>
        </div>

        <!-- Card: Valor del Inventario -->
        <div class="relative p-6 bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group">
             <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i data-lucide="archive" class="w-24 h-24 text-blue-500"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <i data-lucide="layers" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Inventario</span>
                </div>
                <div class="mt-4">
                    <h3 id="kpi-inventario" class="text-3xl font-extrabold text-gray-900 dark:text-white">--</h3>
                    <p class="text-sm text-gray-500 mt-1">Valor total estimado</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Gráficos y Secciones de Alertas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Columna Principal (Gráfico) -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Comportamiento de Ventas</h3>
                <span class="text-xs font-medium px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">Diario</span>
            </div>
            <div class="relative bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4" style="height: 350px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Columna Secundaria (Alertas y Listas) -->
        <div class="space-y-6">
            
            <!-- Panel de Top Clientes -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                 <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <i data-lucide="award" class="w-5 h-5 text-amber-500"></i> Mejores Clientes
                 </h3>
                 <div id="list-top-clientes" class="space-y-3">
                    <!-- Contenido cargado vía JS -->
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-2 bg-gray-200 rounded"></div>
                            <div class="h-2 bg-gray-200 rounded w-3/4"></div>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Sección de Alertas -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500"></i> Bajo Stock
                </h3>
                <div id="list-alerta-stock" class="space-y-3">
                    <!-- Contenido cargado vía JS -->
                     <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-2 bg-gray-200 rounded"></div>
                            <div class="h-2 bg-gray-200 rounded w-3/4"></div>
                        </div>
                    </div>
                </div>
            </div>
           
        </div>
    </div>
</div>

<!-- Incluir Chart.js desde un CDN (Si no se incluye en header) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>