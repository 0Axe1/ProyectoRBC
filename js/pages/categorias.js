document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('categoria-modal');
    if (!modal) return; 

    const addBtn = document.getElementById('add-categoria-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const categoriaForm = document.getElementById('categoria-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const formCategoriaId = document.getElementById('form-categoria-id');
    const submitBtn = document.getElementById('submit-btn');
    
    const messageContainer = document.getElementById('message-container');
    const modalMessageContainer = document.getElementById('modal-message-container');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');

    // --- LÓGICA PARA ABRIR MODAL (CREAR) ---
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            categoriaForm.reset();
            modalTitle.textContent = 'Agregar Nueva Categoría';
            submitBtn.textContent = 'Guardar Categoría';
            formAction.value = 'create';
            formCategoriaId.value = '';
            modalMessageContainer.innerHTML = '';
            openModal();
        });
    }

    // --- LÓGICA PARA ABRIR MODAL (EDITAR) ---
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            categoriaForm.reset();
            modalTitle.textContent = 'Editar Categoría';
            submitBtn.textContent = 'Actualizar Categoría';
            formAction.value = 'update';
            
            formCategoriaId.value = button.dataset.id;
            document.getElementById('nombre_categoria').value = button.dataset.nombre;
            document.getElementById('descripcion').value = button.dataset.descripcion;
            
            modalMessageContainer.innerHTML = '';
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

    // --- MANEJO DE FORMULARIO CON FETCH (CREAR/ACTUALIZAR) ---
    if (categoriaForm) {
        categoriaForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const action = formAction.value;
            const submitText = (action === 'create') ? 'Guardar Categoría' : 'Actualizar Categoría';
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            const formData = new FormData(categoriaForm);

            try {
                const response = await fetch('api/categorias_actions.php', {
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

    // --- MANEJO DE FORMULARIO CON FETCH (ELIMINAR) ---
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const confirmed = typeof window.showConfirmationModal === 'function'
                ? await window.showConfirmationModal('Eliminar Categoría', '¿Estás seguro? No podrás eliminarla si está siendo usada por algún producto.')
                : confirm('¿Estás seguro? No podrás eliminarla si está siendo usada por algún producto.');

            if (!confirmed) return;

            const formData = new FormData(form);
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;

            try {
                const response = await fetch('api/categorias_actions.php', {
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