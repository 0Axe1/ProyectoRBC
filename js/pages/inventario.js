document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('product-modal');
    if (!modal) return;

    const addProductBtn = document.getElementById('add-product-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const productForm = document.getElementById('product-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const formProductId = document.getElementById('form-product-id');
    const submitBtn = document.getElementById('submit-btn');
    const stockInput = document.getElementById('stock');
    const stockContainer = document.getElementById('stock-container');
    const precioInput = document.getElementById('precio');
    const additionalDetailsContainer = document.getElementById('additional-details-container');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => {
        modal.classList.add('hidden');
        const modalMsg = document.getElementById('modal-message-container');
        if (modalMsg) modalMsg.innerHTML = '';
    };

    const fillModalData = (button) => {
        formProductId.value = button.dataset.id;
        document.getElementById('nombre_producto').value = button.dataset.nombre;
        document.getElementById('id_categoria').value = button.dataset.idCategoria;
        precioInput.value = button.dataset.precio ?? '';
        stockInput.value = button.dataset.stock ?? '';
        document.getElementById('descripcion').value = button.dataset.descripcion ?? '';
        document.getElementById('unidad_medida').value = button.dataset.unidadMedida ?? '';
        document.getElementById('peso_neto').value = button.dataset.pesoNeto ?? '';
        document.getElementById('link_documentos').value = button.dataset.linkDocumentos ?? '';
    };

    const setFormEnabled = (isEnabled) => {
        Array.from(productForm.elements).forEach(el => {
            if (el.id !== 'close-modal-btn' && el.id !== 'cancel-btn') {
                el.disabled = !isEnabled;
            }
        });
        if (isEnabled) {
            submitBtn.style.display = 'inline-flex';
            cancelBtn.textContent = 'Cancelar';
        } else {
            submitBtn.style.display = 'none';
            cancelBtn.textContent = 'Cerrar';
        }
    };

    if (addProductBtn) {
        addProductBtn.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Registrar Nuevo Producto';
            submitBtn.textContent = 'Guardar Producto';
            formAction.value = 'create';
            formProductId.value = '';
            setFormEnabled(true);
            stockContainer.style.display = 'block';
            additionalDetailsContainer.classList.add('hidden');
            openModal();
        });
    }

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Editar Información del Producto';
            submitBtn.textContent = 'Actualizar Producto';
            formAction.value = 'update';
            fillModalData(button);
            setFormEnabled(true);
            stockContainer.style.display = 'block';
            additionalDetailsContainer.classList.remove('hidden');
            openModal();
        });
    });

    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Ver Detalle del Producto';
            formAction.value = 'view';
            fillModalData(button);
            setFormEnabled(false);
            additionalDetailsContainer.classList.remove('hidden');
            openModal();
        });
    });

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    // --- Form submit con Fetch ---
    if (productForm) {
        productForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = formAction.value;
            if (action === 'view') return;

            const modalMsg = document.getElementById('modal-message-container');
            if (modalMsg) modalMsg.innerHTML = '';

            const nombre = document.getElementById('nombre_producto').value.trim();
            const idCategoria = document.getElementById('id_categoria').value;
            const stock = parseInt(stockInput.value, 10);
            const precio = parseFloat(precioInput.value);

            if (!nombre || !idCategoria) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('El nombre y la categoría son obligatorios.', 'error', 'modal-message-container');
                }
                return;
            }
            if (action !== 'view' && (isNaN(stock) || stock < 0 || isNaN(precio) || precio <= 0)) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('Stock debe ser mayor o igual a 0. Precio debe ser mayor a 0.', 'error', 'modal-message-container');
                }
                return;
            }

            const submitText = action === 'create' ? 'Guardar Producto' : 'Actualizar Producto';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';

            const formData = new FormData(productForm);

            try {
                const response = await fetch('api/inventario_actions.php', {
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
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const confirmed = typeof window.showConfirmationModal === 'function'
                ? await window.showConfirmationModal('Eliminar Producto', '¿Seguro que quieres eliminar este producto? No se puede borrar si está asociado a un pedido.')
                : confirm('¿Seguro que quieres eliminar este producto? No se puede borrar si está asociado a un pedido.');

            if (!confirmed) return;

            const formData = new FormData(form);
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;

            try {
                const response = await fetch('api/inventario_actions.php', {
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