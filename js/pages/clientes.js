document.addEventListener('DOMContentLoaded', () => {
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

    // --- ¡NUEVO! Selectores para validación ---
    const tipoContactoSelect = document.getElementById('id_tipo_contacto');
    const datoContactoInput = document.getElementById('dato_contacto');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');

    // --- Función para actualizar el input de contacto ---
    const actualizarInputContacto = () => {
        if (!tipoContactoSelect || !datoContactoInput) return;

        const selectedOption = tipoContactoSelect.options[tipoContactoSelect.selectedIndex];
        const tipoNombre = selectedOption.dataset.nombre || 'desconocido';

        // Resetear atributos
        datoContactoInput.removeAttribute('pattern');
        
        if (tipoNombre.includes('email')) {
            datoContactoInput.type = 'email';
            datoContactoInput.placeholder = 'ejemplo@correo.com';
            datoContactoInput.maxLength = 100;
        } else if (tipoNombre.includes('telefono') || tipoNombre.includes('whatsapp')) {
            datoContactoInput.type = 'tel'; // 'tel' es mejor que 'number' para esto
            datoContactoInput.placeholder = 'Ej: 71234567 (solo números)';
            datoContactoInput.pattern = '^[0-9]{7,15}$'; // 7 a 15 dígitos
            datoContactoInput.maxLength = 15;
        } else {
            datoContactoInput.type = 'text';
            datoContactoInput.placeholder = 'Escribe el contacto aquí';
            datoContactoInput.maxLength = 255;
        }
    };

    // --- ¡NUEVO! Event listener para el cambio de tipo de contacto ---
    if (tipoContactoSelect) {
        tipoContactoSelect.addEventListener('change', actualizarInputContacto);
    }

    // --- ¡NUEVO! Funciones de validación ---
    const isValidEmail = (email) => {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    };

    const isValidPhone = (phone) => {
        const re = /^[0-9]{7,15}$/; // Acepta de 7 a 15 dígitos
        return re.test(String(phone));
    };

    // --- LÓGICA PARA ABRIR MODAL (CREAR) ---
    if (addClientBtn) {
        addClientBtn.addEventListener('click', () => {
            clientForm.reset();
            modalTitle.textContent = 'Agregar Nuevo Cliente';
            submitBtn.textContent = 'Guardar Cliente';
            formAction.value = 'create';
            formClientId.value = '';
            tipoContactoSelect.value = '1'; // Resetear al primer valor
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes del modal
            actualizarInputContacto(); // ¡NUEVO! Actualizar input al abrir
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
            datoContactoInput.value = button.dataset.contacto; // ¡MODIFICADO! Usar la variable
            tipoContactoSelect.value = button.dataset.idTipoContacto; // ¡MODIFICADO! Usar la variable
            
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes del modal
            actualizarInputContacto(); // ¡NUEVO! Actualizar input al abrir
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

    // --- Manejo de formulario con Fetch (crear/actualizar) ---
    if (clientForm) {
        clientForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // --- ¡NUEVO! Validaciones Frontend ---
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes previos
            const valorContacto = datoContactoInput.value;
            
            if (valorContacto) { // Solo validar si no está vacío
                const selectedOption = tipoContactoSelect.options[tipoContactoSelect.selectedIndex];
                const tipoNombre = selectedOption.dataset.nombre || 'desconocido';

                if (tipoNombre.includes('email') && !isValidEmail(valorContacto)) {
                    if (typeof window.showMessage === 'function') {
                        window.showMessage('Por favor, introduce un formato de email válido (ej: usuario@correo.com).', 'error', 'modal-message-container');
                    }
                    datoContactoInput.focus();
                    return;
                }
                
                if (tipoNombre.includes('telefono') || tipoNombre.includes('whatsapp')) {
                    if (!isValidPhone(valorContacto)) {
                        if (typeof window.showMessage === 'function') {
                            window.showMessage('El teléfono debe contener solo números, entre 7 y 15 dígitos.', 'error', 'modal-message-container');
                        }
                        datoContactoInput.focus();
                        return;
                    }
                }
            }
            // --- Fin de Validaciones Frontend ---

            const action = formAction.value;
            const submitText = (action === 'create') ? 'Guardar Cliente' : 'Actualizar Cliente';
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

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

                closeModal();
                if (typeof window.showMessage === 'function') {
                    window.showMessage(result.message, 'success', 'message-container');
                }
                
                // Recargar la página para ver los cambios.
                setTimeout(() => {
                    location.reload();
                }, 1500);

            } catch (error) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage(error.message, 'error', 'modal-message-container');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = submitText;
            }
        });
    }

    // --- ¡NUEVO! MANEJO DE FORMULARIO CON FETCH (ELIMINAR) ---
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const confirmed = typeof window.showConfirmationModal === 'function'
                ? await window.showConfirmationModal('Eliminar Cliente', '¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.')
                : confirm('¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.');
            if (!confirmed) return;

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

                if (typeof window.showMessage === 'function') {
                    window.showMessage(result.message, 'success', 'message-container');
                }

                // Recargar la página para ver los cambios
                setTimeout(() => {
                    location.reload();
                }, 1500);

            } catch (error) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage(error.message, 'error', 'message-container');
                }
                button.disabled = false;
            }
        });
    });
});