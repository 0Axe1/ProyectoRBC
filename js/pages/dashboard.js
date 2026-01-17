document.addEventListener('DOMContentLoaded', () => {
    // Datos para el gráfico (inyectados desde PHP en el HTML)
    // El script asume que la variable `chartData` fue definida en dashboard.php
    if (typeof chartData === 'undefined') {
        console.error('chartData no está definido. Asegúrate de que dashboard.php lo esté proveyendo.');
        return;
    }
   
    const ctxEl = document.getElementById('salesChart');
    if (!ctxEl) return; // No ejecutar si el canvas no existe

    // Configuración del gráfico de ventas
    const ctx = ctxEl.getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Ventas (Bs.)',
                    data: chartData.ventas,
                    backgroundColor: 'rgba(34, 197, 94, 0.6)',
                    borderColor: 'rgba(22, 163, 74, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(200, 200, 200, 0.2)' },
                    ticks: {
                        callback: (value) => 'Bs. ' + value.toLocaleString(),
                    }
                },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 14 },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: (context) => {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += 'Bs. ' + context.parsed.y.toLocaleString('es-BO', { minimumFractionDigits: 2 });
                            }
                            return label;
                        },
                    },
                },
            },
        },
    });

    // Lucide se inicializa en app.js, pero si se cargó dinámicamente,
    // podríamos necesitar re-inicializarlo. app.js ya lo hace globalmente.
    // if (typeof lucide !== 'undefined') {
    //     lucide.createIcons();
    // }
});
