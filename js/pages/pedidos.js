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
    const clienteSelect = document.getElementById('id_cliente');
    const fechaInput = document.getElementById('fecha_cotizacion');
    const direccionInput = document.getElementById('direccion_entrega');

    const addItemSection = document.getElementById('add-item-section');
    const viewModeStatus = document.getElementById('view-mode-status');
    // const productoSelect = document.getElementById('producto_select'); // REPLACED
    const productoSearch = document.getElementById('producto_search');
    const idProductoSeleccionado = document.getElementById('id_producto_seleccionado');
    const productoSearchResults = document.getElementById('producto_search_results');
    const cantidadInput = document.getElementById('cantidad');
    const precioInput = document.getElementById('precio');
    const addItemBtn = document.getElementById('add-item-btn');
    const detalleBody = document.getElementById('detalle-pedido-body');
    const totalPedidoEl = document.getElementById('total-pedido');
    const detallePedidoJsonInput = document.getElementById('detalle_pedido_json');

    const modalMessageContainer = document.getElementById('modal-message-container');

    let productosDisponibles = []; // Ya no se carga todo al inicio, se usa para mantener el producto seleccionado actual
    let clientesDisponibles = [];
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
            // Opcional: desaparecer después de unos segundos
            // setTimeout(() => container.innerHTML = '', 5000);
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

    // --- Cargar Datos (Clientes y Productos) ---
    async function fetchFormData() {
        try {
            const response = await fetch('api/pedidos_actions.php?action=get_form_data');
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Error al cargar datos');
            // Guardamos los clientes localmente para acceder a sus direcciones
            clientesDisponibles = data.clientes;

            if (clienteSelect) {
                clienteSelect.innerHTML = '<option value="">Seleccione un cliente</option>';
                clientesDisponibles.forEach(c => {
                    clienteSelect.innerHTML += `<option value="${c.id_cliente}">${c.nombre_razon_social}</option>`;
                });
            }

            // productosDisponibles = data.productos; // REMOVED: Load on demand
            // if (productoSelect) {
            //     productoSelect.innerHTML = '<option value="">Seleccione un producto</option>';
            //     productosDisponibles.forEach(p => {
            //         productoSelect.innerHTML += `<option value="${p.id_producto}">${p.nombre_descriptivo} (Stock: ${p.stock ?? 0})</option>`;
            //     });
            // }
            return data;
        } catch (error) {
            showModalError(`No se pudieron cargar los datos del formulario: ${error.message}`);
        }
    }

    // 2. Lógica de auto-relleno y validación de ubicación
    if (clienteSelect && direccionInput) {
        clienteSelect.addEventListener('change', () => {
            const idCliente = clienteSelect.value;
            const cliente = clientesDisponibles.find(c => c.id_cliente == idCliente);

            if (cliente) {
                // Rellenamos el campo. Si no tiene dirección, enviamos un aviso
                if (cliente.ubicacion && cliente.ubicacion.trim() !== "") {
                    direccionInput.value = cliente.ubicacion;
                    direccionInput.classList.remove('border-red-500'); // Limpiar errores previos
                } else {
                    direccionInput.value = "";
                    showModalError('Este cliente no tiene una dirección registrada. Por favor, ingrésela manualmente.');
                    direccionInput.classList.add('border-red-500');
                }
            } else {
                direccionInput.value = '';
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
        productoSearchResults.classList.add('hidden');
        productoSearchResults.innerHTML = '';
    }

    // --- Lógica de "Agregar Item" ---
    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            modalMessageContainer.innerHTML = '';

            // Defensive check: Ensure elements exist
            if (!idProductoSeleccionado) {
                showModalError('Error interno: No se encuentra el campo ID de producto. Por favor recargue la página (Ctrl+F5).');
                console.error('Elemento id_producto_seleccionado es null');
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

            // Validación extra por seguridad, aunque el ID debería coincidir
            if (!producto || producto.id_producto != idProducto) {
                showModalError('Error al validar el producto seleccionado. Por favor busque nuevamente.');
                return;
            }
            // ... (resto de validación de stock es igual)

            // Calcular stock disponible considerando lo que ya está en el detalle del pedido
            const cantidadYaEnPedido = detallePedido
                .filter(item => item.id == idProducto)
                .reduce((sum, item) => sum + item.cantidad, 0);

            const stockActualParaVenta = (parseInt(producto.stock) || 0) - cantidadYaEnPedido;

            if (stockActualParaVenta < cantidad) {
                showModalError(`Stock insuficiente. Solo hay ${stockActualParaVenta} unidades disponibles para añadir (ya hay ${cantidadYaEnPedido} en este pedido).`);
                return;
            }

            const existingItemIndex = detallePedido.findIndex(item => item.id == idProducto);

            if (existingItemIndex > -1) {
                detallePedido[existingItemIndex].cantidad += cantidad;
                detallePedido[existingItemIndex].precio = precio;
            } else {
                // Usamos nombre_descriptivo si existe, sino el del producto
                detallePedido.push({ id: idProducto, nombre: producto.nombre_descriptivo, cantidad, precio });
            }

            // NOTA: Con búsqueda ajax, ya no actualizamos "productosDisponibles" globalmente 
            // porque no tenemos la lista completa.
            // Simplemente reducimos el stock en nuestra copia local del producto seleccionado 
            // para que si lo vuelve a intentar agregar sin buscar de nuevo, el stock esté actualizado.
            if (currentSelectedProduct) {
                currentSelectedProduct.stock -= cantidad;
            }

            renderDetalle(true);

            // Limpiar campos
            if (productoSearch) productoSearch.value = '';
            if (idProductoSeleccionado) idProductoSeleccionado.value = '';
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
                <tr class="border-b dark:border-gray-700 text-gray-900 dark:text-white">
                    <td class="px-4 py-2 font-medium">${item.nombre}</td>
                    <td class="px-4 py-2 text-center">${item.cantidad}</td>
                    <td class="px-4 py-2 text-center">$ ${item.precio.toFixed(2)}</td>
                    <td class="px-4 py-2 text-right font-bold">$ ${subtotal.toFixed(2)}</td>
                    <td class="px-4 py-2 text-center">
                        ${isEditable ? `<button type="button" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 remove-item-btn" data-index="${index}" data-product-id="${item.id}" data-cantidad="${item.cantidad}"><i data-lucide="x-circle" class="w-5 h-5"></i></button>` : ''}
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
                const productId = removeBtn.dataset.productId;
                const cantidadRestaurar = parseInt(removeBtn.dataset.cantidad);

                detallePedido.splice(index, 1);
                renderDetalle(true);

                // Restaurar stock en productosDisponibles localmente
                // NOTA: Al ser busqueda server-side, esto solo afecta al objeto en memoria si aun existe.
                // Si el usuario busca de nuevo, traerá el stock real de BD (que aun no ha cambiado hasta que se guarde el pedido)
                // Pero para consistencia visual inmediata si re-selecciona el mismo producto SIN buscar:
                /* 
                if (currentSelectedProduct && currentSelectedProduct.id_producto == productId) {
                     currentSelectedProduct.stock += cantidadRestaurar;
                }
                */
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

            formOrderId.value = '';

            // Reset search fields
            if (productoSearch) productoSearch.value = '';
            if (idProductoSeleccionado) idProductoSeleccionado.value = '';
            currentSelectedProduct = null;

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


    // --- Enviar Formulario (SOLO CREAR) ---
    if (orderForm) {
        orderForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const action = formAction.value;
            if (action !== 'create') return;

            modalMessageContainer.innerHTML = '';

            if (!clienteSelect.value) {
                showModalError('Debe seleccionar un cliente.');
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
                showMessage(result.message, 'success', 'message-container');
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                showModalError(error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Crear Pedido';
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