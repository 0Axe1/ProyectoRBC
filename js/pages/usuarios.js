document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('user-modal');
    if (!modal) return;

    const addUserBtn = document.getElementById('add-user-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const form = document.getElementById('user-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const submitBtn = document.getElementById('submit-btn');
    const passwordHelp = document.getElementById('password-help');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => {
        modal.classList.add('hidden');
        const modalMsg = document.getElementById('modal-message-container');
        if (modalMsg) modalMsg.innerHTML = '';
    };

    if (addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            form.reset();
            modalTitle.textContent = 'Agregar Nuevo Usuario';
            formAction.value = 'create';
            submitBtn.textContent = 'Crear Usuario';
            passwordHelp.classList.add('hidden');
            document.getElementById('contrasena').required = true;
            openModal();
        });
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            form.reset();
            modalTitle.textContent = 'Editar Usuario';
            formAction.value = 'update';
            submitBtn.textContent = 'Actualizar Usuario';
            document.getElementById('id_usuario').value = btn.dataset.id;
            document.getElementById('nombre_usuario').value = btn.dataset.nombre;
            document.getElementById('id_rol').value = btn.dataset.idRol;
            passwordHelp.classList.remove('hidden');
            document.getElementById('contrasena').required = false;
            openModal();
        });
    });

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // --- Form submit con Fetch ---
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = formAction.value;
            const modalMsg = document.getElementById('modal-message-container');
            if (modalMsg) modalMsg.innerHTML = '';

            const nombre = document.getElementById('nombre_usuario').value.trim();
            const idRol = document.getElementById('id_rol').value;
            const contrasena = document.getElementById('contrasena').value;

            if (!nombre || !idRol) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('Todos los campos marcados con * son requeridos.', 'error', 'modal-message-container');
                }
                return;
            }
            if (action === 'create' && !contrasena) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('La contraseña es obligatoria para crear un usuario.', 'error', 'modal-message-container');
                }
                return;
            }

            const submitText = action === 'create' ? 'Crear Usuario' : 'Actualizar Usuario';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            const formData = new FormData(form);

            try {
                const response = await fetch('api/usuarios_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || `Error ${response.status}`);

                closeModal();
                if (typeof window.showMessage === 'function') {
                    window.showMessage(result.message, 'success', 'message-container');
                }
                setTimeout(() => location.reload(), 1500);
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

    // --- Eliminar con Fetch y showConfirmationModal ---
    document.querySelectorAll('.delete-form').forEach(formEl => {
        formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const confirmed = typeof window.showConfirmationModal === 'function'
                ? await window.showConfirmationModal('Eliminar Usuario', '¿Estás seguro de que quieres eliminar a este usuario?')
                : confirm('¿Estás seguro de que quieres eliminar a este usuario?');

            if (!confirmed) return;

            const formData = new FormData(formEl);
            const button = formEl.querySelector('button[type="submit"]');
            button.disabled = true;

            try {
                const response = await fetch('api/usuarios_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || `Error ${response.status}`);

                if (typeof window.showMessage === 'function') {
                    window.showMessage(result.message, 'success', 'message-container');
                }
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage(error.message, 'error', 'message-container');
                }
                button.disabled = false;
            }
        });
    });
});
