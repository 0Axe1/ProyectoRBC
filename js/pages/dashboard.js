document.addEventListener('DOMContentLoaded', () => {

    // Referencias a los selectores
    const monthSelect = document.getElementById('filter-month');
    const yearSelect = document.getElementById('filter-year');
    const btnRefresh = document.getElementById('btn-refresh');

    // Referencias a los elementos del DOM a actualizar
    const kpiVentas = document.getElementById('kpi-ventas');
    const kpiPedidos = document.getElementById('kpi-pedidos');
    const kpiInventario = document.getElementById('kpi-inventario');

    const listTopClientes = document.getElementById('list-top-clientes');
    const listAlertaStock = document.getElementById('list-alerta-stock');

    // Variable para la instancia del gráfico
    let salesChartInstance = null;

    // Función principal para cargar datos
    async function loadDashboardData() {
        const month = monthSelect.value;
        const year = yearSelect.value;

        // Mostrar estado de carga (simple opacidad o texto)
        kpiVentas.style.opacity = '0.5';

        try {
            const response = await fetch(`api/dashboard_data.php?month=${month}&year=${year}`);
            if (!response.ok) throw new Error('Error en la respuesta del servidor');

            const data = await response.json();

            if (data.status === 'success') {
                updateDashboardUI(data);
            } else {
                console.error('Error en datos:', data.message);
            }

        } catch (error) {
            console.error('Error cargando dashboard:', error);
            kpiVentas.innerText = 'Error';
        } finally {
            kpiVentas.style.opacity = '1';
        }
    }

    // Función para actualizar el UI
    function updateDashboardUI(data) {
        // 1. Actualizar KPIs
        // Formatear moneda: Bs. X,XXX.XX
        const formatCurrency = (val) => 'Bs. ' + parseFloat(val).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        kpiVentas.innerText = formatCurrency(data.kpis.ventas_periodo);
        kpiPedidos.innerText = data.kpis.pedidos_pendientes;
        kpiInventario.innerText = formatCurrency(data.kpis.valor_inventario);

        // 2. Actualizar Tabla Top Clientes
        if (data.top_clientes.length > 0) {
            listTopClientes.innerHTML = data.top_clientes.map(cliente => `
                <div class="flex justify-between items-center text-sm p-3 rounded-lg bg-gray-50 dark:bg-gray-700/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold text-xs shrink-0">
                            ${cliente.nombre_razon_social.substring(0, 2).toUpperCase()}
                        </div>
                        <p class="font-medium text-gray-700 dark:text-gray-200 truncate">${cliente.nombre_razon_social}</p>
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">${formatCurrency(cliente.total_comprado)}</span>
                </div>
            `).join('');
        } else {
            listTopClientes.innerHTML = `<p class="text-sm text-gray-500 text-center py-4">No hay ventas registradas en este periodo.</p>`;
        }

        // 3. Actualizar Tabla Alerta Stock
        if (data.bajo_stock.length > 0) {
            listAlertaStock.innerHTML = data.bajo_stock.map(prod => `
                <div class="flex justify-between items-center text-sm p-3 rounded-lg bg-red-50 dark:bg-red-900/10 hover:bg-red-100 dark:hover:bg-red-900/20 transition-colors border border-red-100 dark:border-red-900/30">
                     <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-red-600 dark:text-red-400 font-bold text-xs shrink-0">
                            !
                        </div>
                        <p class="font-medium text-gray-700 dark:text-gray-200 truncate">${prod.nombre_producto}</p>
                    </div>
                    <span class="font-bold text-red-600 dark:text-red-400 whitespace-nowrap">${prod.stock} Unid.</span>
                </div>
            `).join('');
        } else {
            listAlertaStock.innerHTML = `<p class="text-sm text-green-500 text-center py-4 flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i> Todo en orden</p>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // 4. Actualizar Gráfico
        renderChart(data.chart.labels, data.chart.data);

        // Re-inicializar iconos Lucide por si acaso se insertaron nuevos en el HTML
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function renderChart(labels, dataValues) {
        const ctxEl = document.getElementById('salesChart');
        if (!ctxEl) return;

        // Destruir instancia anterior si existe
        if (salesChartInstance) {
            salesChartInstance.destroy();
        }

        const ctx = ctxEl.getContext('2d');

        // Crear degradado para las barras
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.8)'); // Indigo 500
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.2)'); // Indigo 500 faded

        salesChartInstance = new Chart(ctx, {
            type: 'bar', // O 'line' si prefieres
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas Diarias',
                    data: dataValues,
                    backgroundColor: gradient,
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        padding: 12,
                        callbacks: {
                            title: (items) => 'Día ' + items[0].label,
                            label: (context) => 'Bs. ' + context.parsed.y.toLocaleString('es-BO', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)',
                            drawBorder: false,
                        },
                        ticks: {
                            callback: (value) => 'Bs. ' + value,
                            font: { size: 10 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // Event Listeners
    monthSelect.addEventListener('change', loadDashboardData);
    yearSelect.addEventListener('change', loadDashboardData);
    if (btnRefresh) btnRefresh.addEventListener('click', loadDashboardData);

    // Cargar datos iniciales
    loadDashboardData();
});
