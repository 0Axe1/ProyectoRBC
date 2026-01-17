/**
 * LÓGICA PARA EL TEMA OSCURO/CLARO 
 * Definimos la función vacía para evitar el error 'is not defined'.
 * Puedes añadir tu lógica de tema oscuro/claro aquí si la tienes.
 */
const applyInitialTheme = () => {
    // Tu lógica para aplicar el tema (ej. localStorage, etc.) va aquí.
    // Si no tienes, déjalo vacío.
};

document.addEventListener('DOMContentLoaded', function() {
    // Esta función se ejecutará solo cuando todo el HTML esté listo.

    /**
     * INICIALIZACIÓN DE ICONOS (LUCIDE)
     * Verificamos si la librería 'lucide' existe en la página antes de usarla.
     */
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Aplicar el tema inicial
    applyInitialTheme(); // Ahora esta función existe y no dará error.


    /**
     * ===================================================================
     * FUNCIÓN GLOBAL PARA MOSTRAR MENSAJES
     * Muestra un mensaje de éxito o error en un contenedor específico.
     * ===================================================================
     */
    window.showMessage = (message, type = 'error', containerId) => {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Message container not found: ${containerId}`);
            // Fallback a alert si el contenedor no existe
            alert(message); 
            return;
        }

        const alertClass = type === 'success' 
            ? 'bg-green-100 border-green-400 text-green-700 dark:bg-green-900/50 dark:border-green-700 dark:text-green-300'
            : 'bg-red-100 border-red-400 text-red-700 dark:bg-red-900/50 dark:border-red-700 dark:text-red-300';
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `p-4 mb-4 text-sm border rounded-lg ${alertClass}`;
        messageDiv.setAttribute('role', 'alert');
        messageDiv.textContent = message;

        container.innerHTML = ''; // Limpiar mensajes anteriores
        container.appendChild(messageDiv);

        // Opcional: hacer que desaparezca después de 5 segundos
        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.5s ease';
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    };


    /**
     * ===================================================================
     * FUNCIÓN GLOBAL PARA MODAL DE CONFIRMACIÓN
     * Muestra un modal y devuelve una Promesa que se resuelve (true/false)
     * ===================================================================
     */
    window.showConfirmationModal = (title, message) => {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmation-modal');
            const titleEl = document.getElementById('confirmation-modal-title');
            const messageEl = document.getElementById('confirmation-modal-message');
            const confirmBtn = document.getElementById('confirmation-modal-confirm-btn');
            const cancelBtn = document.getElementById('confirmation-modal-cancel-btn');

            if (!modal || !titleEl || !messageEl || !confirmBtn || !cancelBtn) {
                console.error('Elementos del modal de confirmación no encontrados.');
                // Fallback al confirm nativo si el modal no está
                resolve(confirm('Error de UI: ' + message)); 
                return;
            }

            // Actualizar textos
            titleEl.textContent = title;
            messageEl.textContent = message;

            // Mostrar modal
            modal.classList.remove('hidden');
            if (typeof lucide !== 'undefined') {
                lucide.createIcons(); // Asegurarse de que el icono se renderice
            }

            // Manejadores de eventos
            // Usamos .cloneNode(true) para remover listeners anteriores
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

            const newCancelBtn = cancelBtn.cloneNode(true);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

            const close = () => {
                modal.classList.add('hidden');
            };

            newConfirmBtn.addEventListener('click', () => {
                close();
                resolve(true); // El usuario confirmó
            });

            newCancelBtn.addEventListener('click', () => {
                close();
                resolve(false); // El usuario canceló
            });
            
            // Cerrar si se hace clic fuera del contenido
            modal.addEventListener('click', (e) => {
                 if (e.target === modal) {
                    close();
                    resolve(false);
                 }
            }, { once: true }); // Solo escuchar este clic una vez
        });
    };

});