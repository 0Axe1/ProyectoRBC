document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('order-modal');
    if (!modal) return;

    const runLucide = () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    const addOrderBtn = document.getElementById('add-order-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const orderForm = document.getElementById('order-form');
    const modalTitle = document.getElementById('modal-title');
    const submitBtn = document.getElementById('submit-btn');

    const formAction = document.getElementById('form-action');
    const formOrderId = document.getElementById('form-order-id');
    const clienteSearch = document.getElementById('cliente_search');
    const idClienteSeleccionado = document.getElementById('id_cliente');
    const clienteSearchResults = document.getElementById('cliente_search_results');
    const fechaInput = document.getElementById('fecha_cotizacion');
    const direccionInput = document.getElementById('direccion_entrega');

    const addItemSection = document.getElementById('add-item-section');
    const viewModeStatus = document.getElementById('view-mode-status');
    const productoSearch = document.getElementById('producto_search');
    const idProductoSeleccionado = document.getElementById('id_producto_seleccionado');
    const productoSearchResults = document.getElementById('producto_search_results');
    const unidadMedidaDisplay = document.getElementById('unidad_medida_display');
    const pesoNetoDisplay = document.getElementById('peso_neto_display');
    const cantidadInput = document.getElementById('cantidad');
    const precioInput = document.getElementById('precio');
    const addItemBtn = document.getElementById('add-item-btn');
    const detalleBody = document.getElementById('detalle-pedido-body');
    const totalPedidoEl = document.getElementById('total-pedido');
    const detallePedidoJsonInput = document.getElementById('detalle_pedido_json');

    const modalMessageContainer = document.getElementById('modal-message-container');

    let detallePedido = []; // Array de objetos: { id, nombre, cantidad, precio }

    const showMessage = (text, type, containerId = 'message-container') => {
        const container = document.getElementById(containerId);
        if (container) {
            let classes = '';
            if (type === 'success') {
                classes = 'text-green-800 bg-green-100 dark:bg-green-200 dark:text-green-800';
            } else if (type === 'error') {
                classes = 'text-red-800 bg-red-100 dark:bg-red-200 dark:text-red-800';
            } else if (type === 'warning') {
                classes = 'text-yellow-800 bg-yellow-100 dark:bg-yellow-200 dark:text-yellow-800';
            }
            container.innerHTML = `<div class="my-4 p-4 text-sm rounded-lg ${classes}" role="alert">${text}</div>`;
        }
    };

    // Asegúrate de que window.showMessage esté disponible globalmente si se usa externamente
    if (!window.showMessage) {
        window.showMessage = showMessage;
    }

    const showModalError = (text) => {
        showMessage(text, 'error', 'modal-message-container');
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        runLucide();
    };
    const closeModal = () => {
        modal.classList.add('hidden');
        if (modalMessageContainer) modalMessageContainer.innerHTML = '';
    };

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // --- Lógica de Búsqueda de Clientes (Autocomplete) ---
    let clienteSearchTimeout;
    let currentSelectedClient = null;

    function selectClient(client) {
        currentSelectedClient = client;
        clienteSearch.value = client.nombre_razon_social;
        idClienteSeleccionado.value = client.id_cliente;
        clienteSearchResults.classList.add('hidden');
        clienteSearchResults.innerHTML = '';

        // Auto-rellenar dirección
        if (direccionInput) {
            if (client.ubicacion && client.ubicacion.trim() !== "") {
                direccionInput.value = client.ubicacion;
                direccionInput.classList.remove('border-red-500');
            } else {
                direccionInput.value = "";
                showModalError('Este cliente no tiene una dirección registrada. Por favor, ingrésela manualmente.');
                direccionInput.classList.add('border-red-500');
            }
        }
    }

    if (clienteSearch && clienteSearchResults) {
        clienteSearch.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            clearTimeout(clienteSearchTimeout);

            // Si el usuario borra o cambia el texto, limpiar la selección
            if (currentSelectedClient && clienteSearch.value !== currentSelectedClient.nombre_razon_social) {
                currentSelectedClient = null;
                idClienteSeleccionado.value = '';
            }

            if (term.length < 2) {
                clienteSearchResults.classList.add('hidden');
                clienteSearchResults.innerHTML = '';
                return;
            }

            clienteSearchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/pedidos_actions.php?action=search_clients&term=${encodeURIComponent(term)}`);
                    const data = await response.json();

                    clienteSearchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(c => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b dark:border-gray-600 last:border-0 text-gray-700 dark:text-gray-200';
                            div.innerHTML = `
                                <div class="font-bold">${c.nombre_razon_social}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">ID: ${c.id_cliente} | Dir: ${c.ubicacion || 'Sin dirección'}</div>
                            `;
                            div.addEventListener('click', () => {
                                selectClient(c);
                            });
                            clienteSearchResults.appendChild(div);
                        });
                        clienteSearchResults.classList.remove('hidden');
                    } else {
                        clienteSearchResults.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron clientes.</div>';
                        clienteSearchResults.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error buscando clientes:', error);
                }
            }, 300); // 300ms debounce
        });

        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!clienteSearch.contains(e.target) && !clienteSearchResults.contains(e.target)) {
                clienteSearchResults.classList.add('hidden');
            }
        });
    }
    // --- Lógica de Búsqueda de Productos (Autocomplete) ---
    let searchTimeout;

    if (productoSearch && productoSearchResults) {
        productoSearch.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            clearTimeout(searchTimeout);

            if (term.length < 2) {
                productoSearchResults.classList.add('hidden');
                productoSearchResults.innerHTML = '';
                return;
            }

            // Ensure High Z-Index for visibility over other modals/elements
            productoSearchResults.classList.remove('z-10');
            productoSearchResults.classList.add('z-50');

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api/pedidos_actions.php?action=search_products&term=${encodeURIComponent(term)}`);
                    const data = await response.json();

                    productoSearchResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(p => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b dark:border-gray-600 last:border-0 text-gray-700 dark:text-gray-200';
                            div.innerHTML = `
                                <div class="font-bold">${p.nombre_descriptivo}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">ID: ${p.id_producto} | Stock: ${p.stock} | Precio: $${p.precio}</div>
                            `;
                            div.addEventListener('click', () => {
                                selectProduct(p);
                            });
                            productoSearchResults.appendChild(div);
                        });
                        productoSearchResults.classList.remove('hidden');
                    } else {
                        productoSearchResults.innerHTML = '<div class="px-4 py-2 text-gray-500 dark:text-gray-400">No se encontraron productos.</div>';
                        productoSearchResults.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error buscando productos:', error);
                }
            }, 300); // 300ms debounce
        });

        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!productoSearch.contains(e.target) && !productoSearchResults.contains(e.target)) {
                productoSearchResults.classList.add('hidden');
            }
        });
    }

    // Función para seleccionar un producto
    let currentSelectedProduct = null;

    function selectProduct(product) {
        currentSelectedProduct = product;
        productoSearch.value = product.nombre_descriptivo;
        idProductoSeleccionado.value = product.id_producto;
        precioInput.value = product.precio;

        if (unidadMedidaDisplay) unidadMedidaDisplay.value = product.unidad_medida || '';
        if (pesoNetoDisplay) pesoNetoDisplay.value = product.peso_neto || '';

        productoSearchResults.classList.add('hidden');
        productoSearchResults.innerHTML = '';
    }

    // --- Lógica de "Agregar Item" ---
    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            modalMessageContainer.innerHTML = '';

            // Defensive check: Ensure elements exist
            if (!idProductoSeleccionado) {
                showModalError('Error interno: No se encuentra el campo ID de producto. Por favor recargue la página.');
                return;
            }

            const idProducto = idProductoSeleccionado.value; // Usamos el input hidden
            const cantidad = parseInt(cantidadInput.value);
            const precio = parseFloat(precioInput.value);

            if (!idProducto) {
                showModalError('Por favor, busque y seleccione un producto.');
                return;
            }
            if (isNaN(cantidad) || cantidad <= 0) {
                showModalError('La cantidad debe ser un número positivo.');
                return;
            }
            if (isNaN(precio) || precio <= 0) {
                showModalError('El precio unitario debe ser un número positivo.');
                return;
            }

            // Usamos el producto seleccionado guardado en la variable global temporal
            const producto = currentSelectedProduct;

            // Validación extra por seguridad
            if (!producto || producto.id_producto != idProducto) {
                showModalError('Error al validar el producto seleccionado. Por favor busque nuevamente.');
                return;
            }

            // Calcular stock disponible considerando lo que ya está en el detalle del pedido
            const cantidadYaEnPedido = detallePedido
                .filter(item => item.id == idProducto)
                .reduce((sum, item) => sum + item.cantidad, 0);

            const stockTotal = parseInt(producto.stock) || 0;
            const stockActualParaVenta = stockTotal - cantidadYaEnPedido;

            if (stockActualParaVenta < cantidad) {
                showModalError(`Stock insuficiente. Disponibles para agregar: ${stockActualParaVenta} (Stock Total: ${stockTotal}, En pedido: ${cantidadYaEnPedido}).`);
                return;
            }

            const existingItemIndex = detallePedido.findIndex(item => item.id == idProducto);

            if (existingItemIndex > -1) {
                detallePedido[existingItemIndex].cantidad += cantidad;
                detallePedido[existingItemIndex].precio = precio;
            } else {
                detallePedido.push({ id: idProducto, nombre: producto.nombre_descriptivo, cantidad, precio });
            }

            // Simplemente reducimos el stock en nuestra copia local del producto seleccionado 
            if (currentSelectedProduct) {
                currentSelectedProduct.stock -= cantidad;
            }

            renderDetalle(true);

            // Limpiar campos
            if (productoSearch) productoSearch.value = '';
            if (idProductoSeleccionado) idProductoSeleccionado.value = '';
            if (unidadMedidaDisplay) unidadMedidaDisplay.value = '';
            if (pesoNetoDisplay) pesoNetoDisplay.value = '';
            cantidadInput.value = '';
            precioInput.value = '';
            currentSelectedProduct = null;
        });
    }

    // --- Renderizar Tabla de Items ---
    function renderDetalle(isEditable = true) {
        if (!detalleBody) return;
        detalleBody.innerHTML = '';
        let total = 0;

        if (detallePedido.length === 0) {
            detalleBody.innerHTML = '<tr><td colspan="5" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">Aún no hay productos en el pedido.</td></tr>';
        }

        detallePedido.forEach((item, index) => {
            const subtotal = item.cantidad * item.precio;
            total += subtotal;
            detalleBody.innerHTML += `
                <tr class="border-b dark:border-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-2 font-medium">${item.nombre}</td>
                    <td class="px-4 py-2 text-center">${item.cantidad}</td>
                    <td class="px-4 py-2 text-center">$ ${item.precio.toFixed(2)}</td>
                    <td class="px-4 py-2 text-right font-bold">$ ${subtotal.toFixed(2)}</td>
                    <td class="px-4 py-2 text-center">
                        ${isEditable ? `<button type="button" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 remove-item-btn p-1 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" data-index="${index}" data-product-id="${item.id}" data-cantidad="${item.cantidad}"><i data-lucide="trash-2" class="w-5 h-5"></i></button>` : ''}
                    </td>
                </tr>
            `;
        });
        if (totalPedidoEl) totalPedidoEl.textContent = `$ ${total.toFixed(2)}`;
        runLucide();
    }

    // --- Event listener para "Quitar Item" ---
    if (detalleBody) {
        detalleBody.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-item-btn');
            if (removeBtn) {
                const index = removeBtn.dataset.index;
                // const productId = removeBtn.dataset.productId;
                // const cantidadRestaurar = parseInt(removeBtn.dataset.cantidad);

                detallePedido.splice(index, 1);
                renderDetalle(true);
            }
        });
    }

    // --- Abrir Modal para "CREAR" ---
    if (addOrderBtn) {
        addOrderBtn.addEventListener('click', async () => {
            orderForm.reset();
            modalTitle.textContent = 'Crear Nuevo Pedido';
            submitBtn.textContent = 'Crear Pedido';
            formAction.value = 'create';
            formOrderId.value = '';

            // Reset client search fields
            if (clienteSearch) clienteSearch.value = '';
            if (idClienteSeleccionado) idClienteSeleccionado.value = '';
            currentSelectedClient = null;

            // Reset product search fields
            if (productoSearch) productoSearch.value = '';
            if (idProductoSeleccionado) idProductoSeleccionado.value = '';
            if (unidadMedidaDisplay) unidadMedidaDisplay.value = '';
            if (pesoNetoDisplay) pesoNetoDisplay.value = '';
            currentSelectedProduct = null;

            detallePedido = [];
            renderDetalle(true);

            addItemSection.classList.remove('hidden');
            viewModeStatus.classList.add('hidden');
            submitBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');

            if (clienteSearch) clienteSearch.disabled = false;
            fechaInput.disabled = false;
            direccionInput.disabled = false;

            fechaInput.valueAsDate = new Date();

            openModal();
        });
    }

    // --- Lógica para "VER" Pedido ---
    document.querySelectorAll('.view-order-btn').forEach(button => {
        button.addEventListener('click', async () => {
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
                'Pendiente': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800',
                'Entregado': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 border border-green-200 dark:border-green-800',
                'Cancelado': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-200 dark:border-red-800',
                'En Preparacion': 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
            };
            const estadoClass = estado_classes[estado] || 'bg-gray-100 text-gray-800';
            viewModeStatus.innerHTML = `<div class="p-4 mb-4 text-sm rounded-lg ${estadoClass} flex items-center justify-between shadow-sm"><div><strong>Estado:</strong> ${estado}</div></div>`;

            if (clienteSearch) {
                clienteSearch.disabled = true;
                clienteSearch.value = button.dataset.clienteNombre;
            }
            if (idClienteSeleccionado) idClienteSeleccionado.value = button.dataset.clienteId;
            fechaInput.disabled = true;
            direccionInput.disabled = true;

            fechaInput.value = button.dataset.fecha;
            direccionInput.value = button.dataset.direccion;

            try {
                const response = await fetch(`api/pedidos_actions.php?action=get_order_details&id=${id}`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Error al cargar detalles');

                detallePedido = data.details.map(item => ({
                    id: item.id_producto,
                    nombre: item.nombre_producto,
                    cantidad: parseInt(item.cantidad_pedido),
                    precio: parseFloat(item.precio_unitario)
                }));

                renderDetalle(false); // Renderizar SIN botones de eliminar

            } catch (error) {
                showModalError(`No se pudieron cargar los detalles del pedido: ${error.message}`);
            }

            openModal();
        });
    });



    // --- Lógica para "EDITAR" Pedido ---
    document.querySelectorAll('.edit-order-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const id = button.dataset.id;
            orderForm.reset();
            modalTitle.textContent = 'Editar Pedido #' + id;
            submitBtn.textContent = 'Actualizar Pedido';
            formAction.value = 'update';
            formOrderId.value = id;

            // Reset client search fields
            if (clienteSearch) clienteSearch.value = '';
            if (idClienteSeleccionado) idClienteSeleccionado.value = '';
            currentSelectedClient = null;

            // Reset product search fields
            if (productoSearch) productoSearch.value = '';
            if (idProductoSeleccionado) idProductoSeleccionado.value = '';
            if (unidadMedidaDisplay) unidadMedidaDisplay.value = '';
            if (pesoNetoDisplay) pesoNetoDisplay.value = '';
            currentSelectedProduct = null;

            // Habilitar secciones para editar
            addItemSection.classList.remove('hidden');
            submitBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');
            viewModeStatus.classList.add('hidden');

            // Habilitar inputs
            if (clienteSearch) clienteSearch.disabled = false;
            fechaInput.disabled = false;
            direccionInput.disabled = false;

            // Pre-llenar datos de cabecera del cliente
            if (clienteSearch) clienteSearch.value = button.dataset.clienteNombre;
            if (idClienteSeleccionado) idClienteSeleccionado.value = button.dataset.clienteId;
            currentSelectedClient = {
                id_cliente: button.dataset.clienteId,
                nombre_razon_social: button.dataset.clienteNombre,
                ubicacion: button.dataset.direccion
            };

            fechaInput.value = button.dataset.fecha;
            direccionInput.value = button.dataset.direccion;

            try {
                const response = await fetch(`api/pedidos_actions.php?action=get_order_details&id=${id}`);
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Error al cargar detalles');

                detallePedido = data.details.map(item => ({
                    id: item.id_producto,
                    nombre: item.nombre_producto,
                    cantidad: parseInt(item.cantidad_pedido),
                    precio: parseFloat(item.precio_unitario)
                }));

                renderDetalle(true); // Renderizar CON botones de eliminar (Editable)

            } catch (error) {
                showModalError(`No se pudieron cargar los detalles del pedido: ${error.message}`);
            }

            openModal();
        });
    });

    // --- Enviar Formulario (CREAR / EDITAR) ---
    if (orderForm) {
        orderForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const action = formAction.value;
            if (action !== 'create' && action !== 'update') return;

            modalMessageContainer.innerHTML = '';

            if (!idClienteSeleccionado.value) {
                showModalError('Debe buscar y seleccionar un cliente.');
                return;
            }
            if (!fechaInput.value) {
                showModalError('Debe seleccionar una fecha.');
                return;
            }
            // Validar que la fecha no sea futura
            const today = new Date().toISOString().split('T')[0];
            if (fechaInput.value > today) {
                showModalError('La fecha de cotización no puede ser futura.');
                return;
            }

            // Validar direccion de entrega
            if (!direccionInput.value.trim()) {
                showModalError('La dirección de entrega es obligatoria.');
                return;
            }
            if (direccionInput.value.trim().length < 5 || direccionInput.value.trim().length > 255) {
                showModalError('La dirección de entrega debe tener entre 5 y 255 caracteres.');
                return;
            }

            if (detallePedido.length === 0) {
                showModalError('Debe agregar al menos un producto al pedido.');
                return;
            }

            // --- PREVENCIÓN DE DOBLE CLICK (FRONTEND) ---
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = 'Procesando...';

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
                showMessage(result.message, 'success', 'message-container');
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                showModalError(error.message);
                // Rehabilitar botón solo si hubo error
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        });
    }

    // --- Formularios de Acción (Entregar / Cancelar) ---
    const handleActionForm = async (e) => {
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

            showMessage(result.message, 'success', 'message-container');
            setTimeout(() => location.reload(), 1500);
        } catch (error) {
            showMessage(error.message, 'error', 'message-container');
            button.disabled = false;
        }
    };

    document.querySelectorAll('.deliver-form').forEach(form => {
        form.addEventListener('submit', handleActionForm);
    });

    document.querySelectorAll('.cancel-form').forEach(form => {
        form.addEventListener('submit', handleActionForm);
    });

});