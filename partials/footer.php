    </div> <!-- Cierre de la etiqueta <div id="app"> de header.php -->

    <!-- =================================================================== -->
    <!-- ¡NUEVO! MODAL DE CONFIRMACIÓN GENÉRICO                           -->
    <!-- =================================================================== -->
    <div id="confirmation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden z-50 p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md transform transition-all duration-300 ease-out">
            <!-- Contenido del modal -->
            <div class="flex items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50 sm:mx-0 sm:h-10 sm:w-10">
                    <!-- Icono de Alerta -->
                    <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600 dark:text-red-400"></i>
                </div>
                <div class="ml-4 mt-0 text-left">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-gray-100" id="confirmation-modal-title">Confirmar Acción</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="confirmation-modal-message">
                            ¿Estás seguro de que deseas realizar esta acción?
                        </p>
                    </div>
                </div>
            </div>
            <!-- Botones de acción -->
            <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse sm:space-x-4 sm:space-x-reverse">
                <button type
="button" id="confirmation-modal-confirm-btn" class="inline-flex justify-center w-full rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:w-auto sm:text-sm">
                    Confirmar
                </button>
                <button type="button" id="confirmation-modal-cancel-btn" class="mt-3 inline-flex justify-center w-full rounded-lg border border-gray-300 dark:border-gray-500 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>


    <!-- =================================================================== -->
    <!-- ORDEN DE SCRIPTS CORRECTO                                         -->
    <!-- 1. Cargar la librería externa (Lucide Icons) PRIMERO.             -->
    <!-- =================================================================== -->
    <script src="https://unpkg.com/lucide-icons@0.378.0/dist/lucide.min.js"></script>

    <!-- =================================================================== -->
    <!-- 2. Cargar tu script personalizado (app.js) DESPUÉS.               -->
    <!--    Ahora, cuando app.js se ejecute, 'lucide' ya existirá.         -->
    <!-- =================================================================== -->
    <script src="js/app.js"></script>

</body>
</html>
