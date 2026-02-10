document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('user-modal');
    if (!modal) return;

    const runLucide = () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    const addUserBtn = document.getElementById('add-user-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const form = document.getElementById('user-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const submitBtn = document.getElementById('submit-btn');
    const passwordHelp = document.getElementById('password-help');
    const passwordRequiredMark = document.getElementById('password-required-mark');

    // --- Constantes de validación (deben coincidir con el backend) ---
    const MIN_USERNAME_LENGTH = 3;
    const MAX_USERNAME_LENGTH = 50;
    const MIN_PASSWORD_LENGTH = 6;

    const openModal = () => {
        modal.classList.remove('hidden');
        runLucide();
    };
    const closeModal = () => {
        modal.classList.add('hidden');
        const modalMsg = document.getElementById('modal-message-container');
        if (modalMsg) modalMsg.innerHTML = '';
    };

    const showModalError = (text) => {
        if (typeof window.showMessage === 'function') {
            window.showMessage(text, 'error', 'modal-message-container');
        }
    };

    // --- Abrir Modal para CREAR ---
    if (addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            form.reset();
            modalTitle.textContent = 'Agregar Nuevo Usuario';
            formAction.value = 'create';
            submitBtn.textContent = 'Crear Usuario';
            passwordHelp.classList.add('hidden');
            if (passwordRequiredMark) passwordRequiredMark.classList.remove('hidden');
            document.getElementById('contrasena').required = true;
            openModal();
        });
    }

    // --- Abrir Modal para EDITAR ---
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
            if (passwordRequiredMark) passwordRequiredMark.classList.add('hidden');
            document.getElementById('contrasena').required = false;
            openModal();
        });
    });

    // --- Cerrar Modal ---
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // --- Form submit con Fetch ---
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = formAction.value;
            const modalMsg = document.getElementById('modal-message-container');
            if (modalMsg) modalMsg.innerHTML = '';

            // --- Validaciones del lado del cliente ---
            const nombre = document.getElementById('nombre_usuario').value.trim();
            const idRol = document.getElementById('id_rol').value;
            const contrasena = document.getElementById('contrasena').value;

            if (!nombre || !idRol) {
                showModalError('Todos los campos marcados con * son requeridos.');
                return;
            }
            if (nombre.length < MIN_USERNAME_LENGTH || nombre.length > MAX_USERNAME_LENGTH) {
                showModalError(`El nombre de usuario debe tener entre ${MIN_USERNAME_LENGTH} y ${MAX_USERNAME_LENGTH} caracteres.`);
                return;
            }
            if (action === 'create' && !contrasena) {
                showModalError('La contraseña es obligatoria para crear un usuario.');
                return;
            }
            if (contrasena && contrasena.length < MIN_PASSWORD_LENGTH) {
                showModalError(`La contraseña debe tener al menos ${MIN_PASSWORD_LENGTH} caracteres.`);
                return;
            }

            // --- Prevención de doble click ---
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.textContent;
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
                showModalError(error.message);
                // Solo rehabilitar en error
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        });
    }

    // --- Eliminar con Fetch y showConfirmationModal ---
    document.querySelectorAll('.delete-form').forEach(formEl => {
        formEl.addEventListener('submit', async (e) => {
            e.preventDefault();
            const confirmed = typeof window.showConfirmationModal === 'function'
                ? await window.showConfirmationModal('Deshabilitar Usuario', '¿Estás seguro de que quieres deshabilitar a este usuario?')
                : confirm('¿Estás seguro de que quieres deshabilitar a este usuario?');

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
