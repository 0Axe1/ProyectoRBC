document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('client-modal');
    if (!modal) return; // Si no está el modal, no ejecutar nada (seguridad)

    const addClientBtn = document.getElementById('add-client-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const clientForm = document.getElementById('client-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const formClientId = document.getElementById('form-client-id');
    const submitBtn = document.getElementById('submit-btn');
    
    // Contenedores de mensajes
    const messageContainer = document.getElementById('message-container');
    const modalMessageContainer = document.getElementById('modal-message-container');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');

    /**
     * Muestra un mensaje de éxito o error.
     * @param {string} type 'success' o 'error'
     * @param {string} text El mensaje a mostrar
     * @param {HTMLElement} container El elemento donde insertar el mensaje
     */
    const showMessage = (type, text, container) => {
        const bgColor = type === 'success' ? 'bg-green-100 dark:bg-green-200' : 'bg-red-100 dark:bg-red-200';
        const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
        
        container.innerHTML = `<div class='my-4 p-4 text-sm ${textColor} ${bgColor} rounded-lg' role='alert'>${text}</div>`;

        // Autocerrar mensaje principal (fuera del modal)
        if (container === messageContainer) {
            setTimeout(() => {
                container.innerHTML = '';
            }, 4000);
        }
    };

    // --- LÓGICA PARA ABRIR MODAL (CREAR) ---
    if (addClientBtn) {
        addClientBtn.addEventListener('click', () => {
            clientForm.reset();
            modalTitle.textContent = 'Agregar Nuevo Cliente';
            submitBtn.textContent = 'Guardar Cliente';
            formAction.value = 'create';
            formClientId.value = '';
            document.getElementById('id_tipo_contacto').value = '1';
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes del modal
            openModal();
        });
    }

    // --- LÓGICA PARA ABRIR MODAL (EDITAR) ---
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            clientForm.reset();
            modalTitle.textContent = 'Editar Cliente';
            submitBtn.textContent = 'Actualizar Cliente';
            formAction.value = 'update';
            
            // Llenar datos desde atributos data-*
            formClientId.value = button.dataset.id;
            document.getElementById('nombre_razon_social').value = button.dataset.nombre;
            document.getElementById('ubicacion').value = button.dataset.ubicacion;
            document.getElementById('nit_ruc').value = button.dataset.nit;
            document.getElementById('dato_contacto').value = button.dataset.contacto;
            document.getElementById('id_tipo_contacto').value = button.dataset.idTipoContacto;
            
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes del modal
            openModal();
        });
    });

    // --- LÓGICA PARA CERRAR MODAL ---
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // --- ¡NUEVO! MANEJO DE FORMULARIO CON FETCH (CREAR/ACTUALIZAR) ---
    if (clientForm) {
        clientForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const action = formAction.value;
            const submitText = (action === 'create') ? 'Guardar Cliente' : 'Actualizar Cliente';
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes previos

            const formData = new FormData(clientForm);

            try {
                const response = await fetch('api/clientes_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || `Error ${response.status}`);
                }

                // Éxito
                closeModal();
                showMessage('success', result.message, messageContainer);
                
                // Recargar la página para ver los cambios.
                setTimeout(() => {
                    location.reload();
                }, 1500);

            } catch (error) {
                // Mostrar error DENTRO del modal
                showMessage('error', error.message, modalMessageContainer);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = submitText;
            }
        });
    }

    // --- ¡NUEVO! MANEJO DE FORMULARIO CON FETCH (ELIMINAR) ---
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!confirm('¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.')) {
                return;
            }

            const formData = new FormData(form);
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;

            try {
                const response = await fetch('api/clientes_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || `Error ${response.status}`);
                }

                // Éxito
                showMessage('success', result.message, messageContainer);

                // Recargar la página para ver los cambios
                setTimeout(() => {
                    location.reload();
                }, 1500);

            } catch (error) {
                showMessage('error', error.message, messageContainer);
                button.disabled = false;
            }
        });
    });
});
