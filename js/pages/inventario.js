document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('product-modal');
    if (!modal) return;

    const runLucide = () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

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
    const toggleDetailsBtn = document.getElementById('toggle-details-btn');
    const toggleDetailsText = document.getElementById('toggle-details-text');
    const toggleDetailsIcon = document.getElementById('toggle-details-icon');

    // --- Constantes de validación (deben coincidir con el backend) ---
    const MAX_NOMBRE_LENGTH = 255;
    const MAX_DESCRIPCION_LENGTH = 1000;

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

    const fillModalData = (button) => {
        formProductId.value = button.dataset.id;
        document.getElementById('nombre_producto').value = button.dataset.nombre;

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

    // --- Toggle para Detalles Adicionales ---
    const updateToggleButton = (isVisible) => {
        if (toggleDetailsText) {
            toggleDetailsText.textContent = isVisible ? 'Ocultar detalles adicionales' : 'Mostrar detalles adicionales';
        }
        if (toggleDetailsIcon) {
            toggleDetailsIcon.setAttribute('data-lucide', isVisible ? 'chevron-up' : 'chevron-down');
            runLucide();
        }
    };

    if (toggleDetailsBtn && additionalDetailsContainer) {
        toggleDetailsBtn.addEventListener('click', () => {
            const isHidden = additionalDetailsContainer.classList.contains('hidden');
            if (isHidden) {
                additionalDetailsContainer.classList.remove('hidden');
            } else {
                additionalDetailsContainer.classList.add('hidden');
            }
            updateToggleButton(isHidden);
        });
    }

    // --- Abrir Modal para CREAR ---
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
            updateToggleButton(false);
            if (toggleDetailsBtn) toggleDetailsBtn.classList.remove('hidden');
            openModal();
        });
    }

    // --- Abrir Modal para EDITAR ---
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
            updateToggleButton(true);
            if (toggleDetailsBtn) toggleDetailsBtn.classList.remove('hidden');
            openModal();
        });
    });

    // --- Abrir Modal para VER ---
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Ver Detalle del Producto';
            formAction.value = 'view';
            fillModalData(button);
            setFormEnabled(false);
            additionalDetailsContainer.classList.remove('hidden');
            updateToggleButton(true);
            if (toggleDetailsBtn) toggleDetailsBtn.classList.add('hidden');
            openModal();
        });
    });

    // --- Cerrar Modal ---
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

            // --- Validaciones del lado del cliente ---
            const nombre = document.getElementById('nombre_producto').value.trim();
            const stock = parseInt(stockInput.value, 10);
            const precio = parseFloat(precioInput.value);
            const descripcion = document.getElementById('descripcion').value.trim();
            const linkDocumentos = document.getElementById('link_documentos').value.trim();

            if (!nombre) {
                showModalError('El nombre del producto es obligatorio.');
                return;
            }
            if (nombre.length > MAX_NOMBRE_LENGTH) {
                showModalError(`El nombre no puede exceder ${MAX_NOMBRE_LENGTH} caracteres.`);
                return;
            }
            if (isNaN(stock) || stock < 0) {
                showModalError('El stock debe ser un número mayor o igual a 0.');
                return;
            }
            if (isNaN(precio) || precio <= 0) {
                showModalError('El precio debe ser un número mayor a 0.');
                return;
            }
            if (descripcion.length > MAX_DESCRIPCION_LENGTH) {
                showModalError(`La descripción no puede exceder ${MAX_DESCRIPCION_LENGTH} caracteres.`);
                return;
            }
            if (linkDocumentos && !linkDocumentos.match(/^https?:\/\/.+/i)) {
                showModalError('El link de documentos debe ser una URL válida (ej: https://...).');
                return;
            }

            // --- Prevención de doble click ---
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.textContent;
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
                showModalError(error.message);
                // Solo rehabilitar en error
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
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