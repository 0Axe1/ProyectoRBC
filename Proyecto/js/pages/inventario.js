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

    const stockInput = document.getElementById('cantidad_stock');
    const stockContainer = document.getElementById('stock-container');
    const precioVentaInput = document.getElementById('precio_venta_sugerido');

    const openModal = () => modal.classList.remove('hidden');
    const closeModal = () => modal.classList.add('hidden');
   
    // Configurar modal para CREAR
    if(addProductBtn) {
        addProductBtn.addEventListener('click', () => {
            productForm.reset();
            modalTitle.textContent = 'Registrar Nuevo Producto';
            submitBtn.textContent = 'Guardar Producto';
            formAction.value = 'create';
            formProductId.value = '';
           
            // Habilitar y mostrar campo de stock
            stockInput.disabled = false;
            stockInput.required = true;
            stockContainer.style.display = 'block';
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
           
            // Llenar datos
            formProductId.value = button.dataset.id;
            document.getElementById('nombre_producto').value = button.dataset.nombre;
            document.getElementById('id_categoria').value = button.dataset.idCategoria;
            document.getElementById('descripcion_producto').value = button.dataset.descripcion;
            precioVentaInput.value = button.dataset.precioVenta;
           
            // En modo edición, no se edita el stock
            stockInput.disabled = true;
            stockInput.required = false; // No es requerido al actualizar
            stockInput.value = button.dataset.stock; // Mostrar stock actual
            stockContainer.style.display = 'none'; // Ocultar campo de stock

            openModal();
        });
    });

    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // Lógica para mensajes (si se migra a fetch)
    const messageContainer = document.getElementById('message-container');
    if (messageContainer && messageContainer.children.length > 0) {
        setTimeout(() => {
            messageContainer.style.transition = 'opacity 0.5s ease';
            messageContainer.style.opacity = '0';
            setTimeout(() => messageContainer.remove(), 500);
        }, 4000);
    }
});
