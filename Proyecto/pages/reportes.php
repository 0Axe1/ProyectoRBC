<div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md fade-in">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">Generación de Reportes</h3>
   
    <form id="report-form" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border dark:border-gray-600">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            <!-- Selector de Tipo de Reporte -->
            <div class="md:col-span-2">
                <label for="report-type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Reporte</label>
                <select id="report-type" name="report_type" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                    <option value="">-- Seleccione un reporte --</option>
                    <option value="ventas">Reporte de Ventas por Fecha</option>
                    <option value="inventario">Reporte de Inventario General</option>
                    <option value="clientes">Reporte de Clientes</option>
                </select>
            </div>
             
             <!-- Selectores de Fecha (inicialmente ocultos) -->
             <div id="date-pickers" class="hidden md:col-span-2 grid grid-cols-2 gap-4">
                 <div>
                    <label for="start-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Inicio</label>
                    <input type="date" id="start-date" name="start_date" class="mt-1 block w-full pl-3 pr-4 py-2 text-base border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                </div>
                 <div>
                    <label for="end-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Fin</label>
                    <input type="date" id="end-date" name="end_date" class="mt-1 block w-full pl-3 pr-4 py-2 text-base border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                </div>
            </div>

            <!-- Botón para generar el reporte -->
            <div id="button-container" class="md:col-span-4 flex justify-end">
                 <button type="submit" class="w-full md:w-auto flex items-center justify-center bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition-transform transform hover:scale-105 duration-300">
                    <i data-lucide="play" class="w-5 h-5 mr-2"></i>
                    Generar Reporte
                </button>
            </div>
        </div>
    </form>

    <!-- Contenedor para mostrar los resultados -->
    <div id="report-results" class="mt-8">
        <div id="loading-indicator" class="hidden text-center p-8">
            <p class="text-gray-500 dark:text-gray-400">Generando reporte...</p>
        </div>
        <div id="results-content"></div>
    </div>
</div>

<!-- ¡CAMBIO! SCRIPT ELIMINADO -->
