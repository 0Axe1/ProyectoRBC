document.addEventListener('DOMContentLoaded', function() {
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
   
    const openModal = () => {
        modal.classList.remove('hidden');
        runLucide();
    };
    const closeModal = () => modal.classList.add('hidden');

    if(addOrderBtn) addOrderBtn.addEventListener('click', openModal);
    if(closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if(cancelBtn) cancelBtn.addEventListener('click', closeModal);

    let productosDisponibles = [];
    let detallePedido = [];

    const clienteSelect = document.getElementById('id_cliente');
    const productoSelect = document.getElementById('producto_select');
    const addItemBtn = document.getElementById('add-item-btn');
    const detalleBody = document.getElementById('detalle-pedido-body');
    const totalPedidoEl = document.getElementById('total-pedido');
    const detallePedidoJsonInput = document.getElementById('detalle_pedido_json');
    const orderForm = document.getElementById('order-form');
   
    if(addOrderBtn) {
        addOrderBtn.addEventListener('click', () => {
            document.getElementById('fecha_cotizacion').valueAsDate = new Date();
            detallePedido = [];
            renderDetalle();
            fetchFormData();
        });
    }

    async function fetchFormData() {
        try {
            const response = await fetch('api/pedidos_actions.php?action=get_form_data');
            const responseText = await response.text(); // Leer como texto primero
            if (!response.ok) {
                let errorMsg = `Error del Servidor: ${response.status}.`;
                try {
                    const errorJson = JSON.parse(responseText);
                    errorMsg += ` ${errorJson.error || 'Sin detalles.'}`;
                } catch (e) {
                    errorMsg += " La respuesta del servidor no es un JSON válido. Respuesta recibida: " + responseText;
                }
                throw new Error(errorMsg);
            }
            
            const data = JSON.parse(responseText); // Parsear a JSON si la respuesta fue OK
           
            if(clienteSelect) {
                clienteSelect.innerHTML = '<option value="">Seleccione un cliente</option>';
                data.clientes.forEach(c => {
                    clienteSelect.innerHTML += `<option value="${c.id_cliente}">${c.nombre_razon_social}</option>`;
                });
            }

            productosDisponibles = data.productos;
            if(productoSelect) {
                productoSelect.innerHTML = '<option value="">Seleccione un producto</option>';
                productosDisponibles.forEach(p => {
                    productoSelect.innerHTML += `<option value="${p.id_producto}">${p.nombre_producto} (Stock: ${p.stock ?? 0})</option>`;
                });
            }

        } catch (error) {
            console.error('Error detallado al cargar datos:', error);
            alert('No se pudieron cargar los datos para el formulario.\n\nDetalle: ' + error.message);
        }
    }

    if(addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            const idProducto = productoSelect.value;
            const cantidad = parseInt(document.getElementById('cantidad').value);
            const precio = parseFloat(document.getElementById('precio').value);

            if (!idProducto || !cantidad || !precio || cantidad <= 0 || precio <= 0) {
                alert('Por favor, complete todos los campos del producto.');
                return;
            }

            const producto = productosDisponibles.find(p => p.id_producto == idProducto);
            if (!producto) {
                alert('Producto no encontrado.');
                return;
            }
            
            if (producto.stock < cantidad) {
                alert(`Stock insuficiente. Solo hay ${producto.stock} unidades disponibles.`);
                return;
            }

            if (detallePedido.some(item => item.id == idProducto)) {
                alert('Este producto ya está en el pedido.');
                return;
            }

            detallePedido.push({ id: idProducto, nombre: producto.nombre_producto, cantidad, precio });
            renderDetalle();
           
            productoSelect.value = '';
            document.getElementById('cantidad').value = '';
            document.getElementById('precio').value = '';
        });
    }

    function renderDetalle() {
        if(!detalleBody) return;
        detalleBody.innerHTML = '';
        let total = 0;
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
                        <button type="button" class="text-red-500 remove-item-btn" data-index="${index}"><i data-lucide="x-circle" class="w-5 h-5"></i></button>
                    </td>
                </tr>
            `;
        });
        if(totalPedidoEl) totalPedidoEl.textContent = `$ ${total.toFixed(2)}`;
        runLucide();
    }   

    if(detalleBody) {
        detalleBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item-btn')) {
                const index = e.target.closest('.remove-item-btn').dataset.index;
                detallePedido.splice(index, 1);
                renderDetalle();
            }
        });
    }

    if(orderForm) {
        orderForm.addEventListener('submit', function(e) {
            if (detallePedido.length === 0) {
                e.preventDefault();
                alert('Debe agregar al menos un producto al pedido.');
                return;
            }
            if(detallePedidoJsonInput) {
                detallePedidoJsonInput.value = JSON.stringify(detallePedido);
            }
        });
    }
   
    const messageContainer = document.getElementById('message-container');
    if (messageContainer && messageContainer.children.length > 0) {
        setTimeout(() => {
            messageContainer.style.transition = 'opacity 0.5s ease';
            messageContainer.style.opacity = '0';
            setTimeout(() => messageContainer.remove(), 500);
        }, 4000);
    }
});
