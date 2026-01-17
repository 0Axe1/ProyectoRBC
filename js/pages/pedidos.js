document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('order-modal');
    if (!modal) return;
   
    const runLucide = () => {
// ... (código de runLucide sin cambios) ...
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    // --- Selectores del Modal ---
// ... (código de selectores sin cambios) ...
    const addOrderBtn = document.getElementById('add-order-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const orderForm = document.getElementById('order-form');
    const modalTitle = document.getElementById('modal-title');
    const submitBtn = document.getElementById('submit-btn');
    
    // --- Selectores del Formulario ---
    const formAction = document.getElementById('form-action');
// ... (código de selectores sin cambios) ...
    const formOrderId = document.getElementById('form-order-id');
    const clienteSelect = document.getElementById('id_cliente');
    const fechaInput = document.getElementById('fecha_cotizacion');
    const direccionInput = document.getElementById('direccion_entrega');
    
    // --- Selectores de Items ---
    const addItemSection = document.getElementById('add-item-section');
// ... (código de selectores sin cambios) ...
    const viewModeStatus = document.getElementById('view-mode-status'); 
    const productoSelect = document.getElementById('producto_select');
    const addItemBtn = document.getElementById('add-item-btn');
    const detalleBody = document.getElementById('detalle-pedido-body');
    const totalPedidoEl = document.getElementById('total-pedido');
    const detallePedidoJsonInput = document.getElementById('detalle_pedido_json');
    
    // --- Contenedores de Mensajes ---
// ... (código de selectores sin cambios) ...
    const messageContainer = document.getElementById('message-container');
    const modalMessageContainer = document.getElementById('modal-message-container');

    // --- Estado ---
// ... (código de estado sin cambios) ...
    let productosDisponibles = [];
    let detallePedido = []; // Array de objetos: { id, nombre, cantidad, precio }

    // --- Funciones de Mensajes ---
    const showMessage = (type, text, container) => {
// ... (código de showMessage sin cambios) ...
        if (typeof window.showMessage === 'function' && container === messageContainer) {
            window.showMessage(text, type, 'message-container');
        } else {
             const bgColor = type === 'success' ? 'bg-green-100 dark:bg-green-200' : 'bg-red-100 dark:bg-red-200';
             const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
             container.innerHTML = `<div class='my-4 p-4 text-sm ${textColor} ${bgColor} rounded-lg' role='alert'>${text}</div>`;

             if (container === messageContainer) {
                setTimeout(() => { container.innerHTML = ''; }, 4000);
             }
        }
    };

    // --- Funciones del Modal ---
    const openModal = () => {
// ... (código de openModal sin cambios) ...
        modal.classList.remove('hidden');
        runLucide();
    };
    const closeModal = () => {
// ... (código de closeModal sin cambios) ...
        modal.classList.add('hidden');
        modalMessageContainer.innerHTML = '';
    };

    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // --- Cargar Datos (Clientes y Productos) ---
    async function fetchFormData() {
        try {
            const response = await fetch('api/pedidos_actions.php?action=get_form_data');
// ... (código de fetch sin cambios) ...
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Error al cargar datos');

            if(clienteSelect) {
// ... (código de clienteSelect sin cambios) ...
                clienteSelect.innerHTML = '<option value="">Seleccione un cliente</option>';
                data.clientes.forEach(c => {
                    clienteSelect.innerHTML += `<option value="${c.id_cliente}">${c.nombre_razon_social}</option>`;
                });
            }

            // Guardamos los productos (incluyendo 'stock' y 'precio')
            productosDisponibles = data.productos;
            if(productoSelect) {
                productoSelect.innerHTML = '<option value="">Seleccione un producto</option>';
                productosDisponibles.forEach(p => {
                    // --- ¡MODIFICADO! ---
                    // Ahora usamos 'nombre_descriptivo' que viene de la API
                    productoSelect.innerHTML += `<option value="${p.id_producto}">${p.nombre_descriptivo} (Stock: ${p.stock ?? 0})</option>`;
                });
            }
            return data;
        } catch (error) {
// ... (código de catch sin cambios) ...
            showMessage('error', `No se pudieron cargar los datos del formulario: ${error.message}`, modalMessageContainer);
        }
    }

    // --- ¡MEJORA! Auto-rellenar precio al seleccionar producto ---
    if (productoSelect) {
// ... (código de auto-rellenar precio sin cambios) ...
        productoSelect.addEventListener('change', () => {
            const idProducto = productoSelect.value;
            const producto = productosDisponibles.find(p => p.id_producto == idProducto);
            const precioInput = document.getElementById('precio');
            
            if (producto && precioInput) {
                precioInput.value = producto.precio || '';
            } else if (precioInput) {
                precioInput.value = '';
            }
        });
    }

    // --- Lógica de "Agregar Item" ---
    if(addItemBtn) {
        addItemBtn.addEventListener('click', () => {
// ... (código de validación sin cambios) ...
            modalMessageContainer.innerHTML = '';
            const idProducto = productoSelect.value;
            const cantidad = parseInt(document.getElementById('cantidad').value);
            const precio = parseFloat(document.getElementById('precio').value);

            if (!idProducto || !cantidad || !precio) {
                showMessage('error', 'Por favor, complete todos los campos del producto.', modalMessageContainer);
                return;
            }
            if (cantidad <= 0 || precio <= 0) {
                 showMessage('error', 'La cantidad y el precio deben ser mayores a cero.', modalMessageContainer);
                return;
            }

            const producto = productosDisponibles.find(p => p.id_producto == idProducto);
            if (!producto) {
                 showMessage('error', 'Producto no encontrado.', modalMessageContainer);
                return;
            }
            
            const stockActual = parseInt(producto.stock) || 0;
// ... (código de validación de stock sin cambios) ...
            if (stockActual < cantidad) {
                showMessage('error', `Stock insuficiente. Solo hay ${stockActual} unidades disponibles.`, modalMessageContainer);
                return;
            }

            if (detallePedido.some(item => item.id == idProducto)) {
// ... (código de validación de duplicado sin cambios) ...
                showMessage('error', 'Este producto ya está en el pedido.', modalMessageContainer);
                return;
            }

            // --- ¡MODIFICADO! ---
            // Usamos 'nombre_descriptivo' al agregar
            detallePedido.push({ id: idProducto, nombre: producto.nombre_descriptivo, cantidad, precio });
            renderDetalle(true);
           
// ... (código de limpiar inputs sin cambios) ...
            productoSelect.value = '';
            document.getElementById('cantidad').value = '';
            document.getElementById('precio').value = '';
        });
    }

    // --- Renderizar Tabla de Items ---
    function renderDetalle(isEditable = true) {
// ... (código de renderDetalle sin cambios) ...
        if(!detalleBody) return;
        detalleBody.innerHTML = '';
        let total = 0;
        
        if(detallePedido.length === 0) {
             detalleBody.innerHTML = '<tr><td colspan="5" class="px-4 py-3 text-center text-gray-500">Aún no hay productos en el pedido.</td></tr>';
        }

        detallePedido.forEach((item, index) => {
            const subtotal = item.cantidad * item.precio;
            total += subtotal;
            detalleBody.innerHTML += `
                <tr class="border-b dark:border-gray-700">
                    <td class="px-4 py-2 font-medium">${item.nombre}</td>
                    <td class="px-4 py-2 text-center">${item.cantidad}</td>
                    <td class="px-4 py-2 text-center">$ ${item.precio.toFixed(2)}</td>
                    <td class="px-4 py-2 text-right font-bold">$ ${subtotal.toFixed(2)}</td>
                    <td class="px-4 py-2 text-center">
                        ${isEditable ? `<button type="button" class="text-red-500 remove-item-btn" data-index="${index}"><i data-lucide="x-circle" class="w-5 h-5"></i></button>` : ''}
                    </td>
                </tr>
            `;
        });
        if(totalPedidoEl) totalPedidoEl.textContent = `$ ${total.toFixed(2)}`;
        runLucide();
    }   

    // --- Event listener para "Quitar Item" ---
    if(detalleBody) {
// ... (código de quitar item sin cambios) ...
        detalleBody.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-item-btn');
            if (removeBtn) {
                const index = removeBtn.dataset.index;
                detallePedido.splice(index, 1);
                renderDetalle(true);
            }
        });
    }

    // --- Abrir Modal para "CREAR" ---
    if(addOrderBtn) {
// ... (código de 'crear' sin cambios) ...
        addOrderBtn.addEventListener('click', async () => {
            orderForm.reset();
            modalTitle.textContent = 'Crear Nuevo Pedido';
            submitBtn.textContent = 'Crear Pedido';
            formAction.value = 'create';
            formOrderId.value = '';
            
            detallePedido = [];
            renderDetalle(true);
            
            addItemSection.classList.remove('hidden');
            viewModeStatus.classList.add('hidden');
            submitBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');
            
            clienteSelect.disabled = false;
            fechaInput.disabled = false;
            direccionInput.disabled = false;

            fechaInput.valueAsDate = new Date();
            
            await fetchFormData();
            openModal();
        });
    }

    // --- Lógica para "VER" Pedido ---
    document.querySelectorAll('.view-order-btn').forEach(button => {
        button.addEventListener('click', async () => {
// ... (código de 'ver' sin cambios) ...
            const id = button.dataset.id;
            
            orderForm.reset();
            modalTitle.textContent = 'Ver Detalle Pedido #' + id;
            formAction.value = 'view';
            formOrderId.value = id;

            addItemSection.classList.add('hidden');
            submitBtn.classList.add('hidden');
            cancelBtn.classList.remove('hidden'); // Dejamos "Cancelar" para que funcione como "Cerrar"
            viewModeStatus.classList.remove('hidden');

            const estado = button.dataset.estado;
            const estado_classes = {
                'Pendiente': 'bg-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                'Entregado': 'bg-green-200 text-green-800 dark:bg-green-900 dark:text-green-300',
                'Cancelado': 'bg-red-200 text-red-800 dark:bg-red-900 dark:text-red-300',
                'En Preparacion': 'bg-blue-200 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            };
            const estadoClass = estado_classes[estado] || 'bg-gray-200 text-gray-800';
            viewModeStatus.innerHTML = `<div class="p-4 mb-4 text-sm rounded-lg ${estadoClass}"><strong>Estado:</strong> ${estado}</div>`;

            clienteSelect.disabled = true;
            fechaInput.disabled = true;
            direccionInput.disabled = true;

            clienteSelect.innerHTML = `<option value="${button.dataset.clienteId}">${button.dataset.clienteNombre}</option>`;
            clienteSelect.value = button.dataset.clienteId;
            fechaInput.value = button.dataset.fecha;
            direccionInput.value = button.dataset.direccion;

            try {
                const response = await fetch(`api/pedidos_actions.php?action=get_order_details&id=${id}`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Error al cargar detalles');
                
                // Esta parte ya funciona con la API actualizada,
                // porque la API devuelve el nombre descriptivo en el alias 'nombre_producto'
                detallePedido = data.details.map(item => ({
                    id: item.id_producto,
                    nombre: item.nombre_producto,
                    cantidad: parseInt(item.cantidad_pedido),
                    precio: parseFloat(item.precio_unitario)
                }));
                
                renderDetalle(false); // Renderizar SIN botones de eliminar
                
            } catch (error) {
                showMessage('error', `No se pudieron cargar los detalles del pedido: ${error.message}`, modalMessageContainer);
            }

            openModal();
        });
    });


    // --- Enviar Formulario (SOLO CREAR) ---
    if(orderForm) {
// ... (código de submit sin cambios) ...
        orderForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const action = formAction.value;
            if (action !== 'create') return; 

            modalMessageContainer.innerHTML = '';
            
            if (!clienteSelect.value) {
                showMessage('error', 'Debe seleccionar un cliente.', modalMessageContainer);
                return;
            }
             if (!fechaInput.value) {
                showMessage('error', 'Debe seleccionar una fecha.', modalMessageContainer);
                return;
            }
            if (detallePedido.length === 0) {
                showMessage('error', 'Debe agregar al menos un producto al pedido.', modalMessageContainer);
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            
            detallePedidoJsonInput.value = JSON.stringify(detallePedido);
            
            const formData = new FormData(orderForm);

            try {
                const response = await fetch('api/pedidos_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (!response.ok) throw new Error(result.message || `Error ${response.status}`);

                closeModal();
                window.showMessage(result.message, 'success', 'message-container');
                
                setTimeout(() => {
                    location.reload();
                }, 1000);

            } catch (error) {
                showMessage('error', error.message, modalMessageContainer);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Pedido';
            }
        });
    }
    
    // --- Formularios de Acción (Entregar / Cancelar) ---
    const handleActionForm = async (e) => {
// ... (código de handleActionForm sin cambios) ...
        e.preventDefault();
        const form = e.target;
        const action = form.querySelector('input[name="action"]').value;
        const confirmText = action === 'deliver'
            ? '¿Confirmar que este pedido ha sido entregado? Esta acción creará un registro de venta.'
            : '¿Estás seguro de que quieres cancelar este pedido? Esta acción repondrá el stock.';
        
        let confirmed = false;
        if (typeof window.showConfirmationModal === 'function') {
            const title = action === 'deliver' ? 'Confirmar Entrega' : 'Confirmar Cancelación';
            confirmed = await window.showConfirmationModal(title, confirmText);
        } else {
            confirmed = confirm(confirmText);
        }

        if (!confirmed) return;

        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        const formData = new FormData(form);

        try {
            const response = await fetch('api/pedidos_actions.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || `Error ${response.status}`);

            window.showMessage(result.message, 'success', 'message-container');

            setTimeout(() => {
                location.reload();
            }, 1000);

        } catch (error) {
            window.showMessage(error.message, 'error', 'message-container');
            button.disabled = false;
        }
    };

    document.querySelectorAll('.deliver-form').forEach(form => {
// ... (código de listeners sin cambios) ...
        form.addEventListener('submit', handleActionForm);
    });
    
    document.querySelectorAll('.cancel-form').forEach(form => {
        form.addEventListener('submit', handleActionForm);
    });

});