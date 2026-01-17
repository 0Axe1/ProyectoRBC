document.addEventListener('DOMContentLoaded', function() {
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

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');
   
    // --- ¡NUEVO! Función para poblar datos y habilitar/deshabilitar campos ---
    const fillModalData = (button) => {
        formProductId.value = button.dataset.id;
        document.getElementById('nombre_producto').value = button.dataset.nombre;
        document.getElementById('id_categoria').value = button.dataset.idCategoria;
        precioInput.value = button.dataset.precio; 
        stockInput.value = button.dataset.stock; 
        document.getElementById('descripcion').value = button.dataset.descripcion;
        document.getElementById('variedad').value = button.dataset.variedad;
        document.getElementById('origen').value = button.dataset.origen;
        document.getElementById('presentacion').value = button.dataset.presentacion;
        document.getElementById('unidad_medida').value = button.dataset.unidadMedida;
        document.getElementById('peso_neto').value = button.dataset.pesoNeto;
        document.getElementById('calidad').value = button.dataset.calidad;
        document.getElementById('fecha_cosecha').value = button.dataset.fechaCosecha;
        document.getElementById('observaciones').value = button.dataset.observaciones;
    };

    const setFormEnabled = (isEnabled) => {
        // Recorrer todos los elementos del formulario
        Array.from(productForm.elements).forEach(el => {
            // No deshabilitar botones de control del modal
            if (el.id !== 'close-modal-btn' && el.id !== 'cancel-btn') {
                el.disabled = !isEnabled;
            }
        });

        // Ocultar/mostrar botones principales
        if (isEnabled) {
            submitBtn.style.display = 'inline-flex';
            cancelBtn.textContent = 'Cancelar';
        } else {
            submitBtn.style.display = 'none';
            cancelBtn.textContent = 'Cerrar'; // El botón "Cancelar" ahora solo cierra
        }
    };
    // --- FIN DE FUNCIONES NUEVAS ---

    // Configurar modal para CREAR
    if(addProductBtn) {
        addProductBtn.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Registrar Nuevo Producto';
            submitBtn.textContent = 'Guardar Producto';
            formAction.value = 'create';
            formProductId.value = '';
            
            setFormEnabled(true); // Habilitar todos los campos
            stockContainer.style.display = 'block'; // Asegurarse que stock sea visible
            openModal();
        });
    }

    // Configurar modal para EDITAR
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Editar Información del Producto';
            submitBtn.textContent = 'Actualizar Producto';
            formAction.value = 'update';
           
            fillModalData(button); // Llenar datos
            setFormEnabled(true); // Habilitar todos los campos
            stockContainer.style.display = 'block'; // Asegurarse que stock sea visible

            openModal();
        });
    });

    // --- ¡NUEVO! Configurar modal para "VER" ---
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Ver Detalle del Producto';
            formAction.value = 'view'; // Ponerlo en modo 'view'
           
            fillModalData(button); // Llenar datos
            setFormEnabled(false); // Deshabilitar todos los campos

            openModal();
        });
    });
    // --- FIN DE "VER" ---

    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // Lógica para auto-cerrar mensajes
    const messageContainer = document.getElementById('message-container');
// ... (código de auto-cierre de mensajes sin cambios) ...
    if (messageContainer && messageContainer.children.length > 0) {
        setTimeout(() => {
            messageContainer.style.transition = 'opacity 0.5s ease';
            messageContainer.style.opacity = '0';
            setTimeout(() => messageContainer.innerHTML = '', 500);
        }, 4000);
    }
});