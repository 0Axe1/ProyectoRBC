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

    // --- ¡NUEVO! Selectores para validación ---
    const tipoContactoSelect = document.getElementById('id_tipo_contacto');
    const datoContactoInput = document.getElementById('dato_contacto');

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

    // --- ¡NUEVO! Función para actualizar el input de contacto ---
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

    // --- ¡MODIFICADO! MANEJO DE FORMULARIO CON FETCH (CREAR/ACTUALIZAR) ---
    if (clientForm) {
        clientForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // --- ¡NUEVO! Validaciones Frontend ---
            modalMessageContainer.innerHTML = ''; // Limpiar mensajes previos
            const valorContacto = datoContactoInput.value;
            
            if (valorContacto) { // Solo validar si no está vacío
                const selectedOption = tipoContactoSelect.options[tipoContactoSelect.selectedIndex];
                const tipoNombre = selectedOption.dataset.nombre || 'desconocido';

                if (tipoNombre.includes('email') && !isValidEmail(valorContacto)) {
                    showMessage('error', 'Por favor, introduce un formato de email válido (ej: usuario@correo.com).', modalMessageContainer);
                    datoContactoInput.focus();
                    return;
                }
                
                if (tipoNombre.includes('telefono') || tipoNombre.includes('whatsapp')) {
                    if (!isValidPhone(valorContacto)) {
                        showMessage('error', 'El teléfono debe contener solo números, entre 7 y 15 dígitos.', modalMessageContainer);
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

            // ¡MODIFICADO! Usar un custom confirm modal sería mejor, pero window.confirm() es funcional.
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