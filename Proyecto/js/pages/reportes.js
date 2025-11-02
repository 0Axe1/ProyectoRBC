document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('report-form');
    if(!reportForm) return;

    const reportTypeSelect = document.getElementById('report-type');
    const datePickersContainer = document.getElementById('date-pickers');
    const buttonContainer = document.getElementById('button-container');
    const resultsContent = document.getElementById('results-content');
    const loadingIndicator = document.getElementById('loading-indicator');

    // Muestra u oculta los selectores de fecha basado en el tipo de reporte
    reportTypeSelect.addEventListener('change', function() {
        if (this.value === 'ventas') {
            datePickersContainer.classList.remove('hidden');
            datePickersContainer.classList.add('md:grid');
            buttonContainer.classList.remove('md:col-span-4');
            buttonContainer.classList.add('md:col-span-2');
        } else {
            datePickersContainer.classList.add('hidden');
            datePickersContainer.classList.remove('md:grid');
            buttonContainer.classList.add('md:col-span-4');
            buttonContainer.classList.remove('md:col-span-2');
        }
    });

    // Maneja el envío del formulario con JavaScript (Fetch API)
    reportForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // Evita que la página se recargue

        loadingIndicator.classList.remove('hidden');
        resultsContent.innerHTML = ''; // Limpia resultados anteriores

        const formData = {
            report_type: document.getElementById('report-type').value,
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value,
        };

        try {
            const response = await fetch('api/reportes_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Ocurrió un error en el servidor.');
            }

            generateTable(result);

        } catch (error) {
            resultsContent.innerHTML = `<div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">${error.message}</div>`;
        } finally {
            loadingIndicator.classList.add('hidden');
        }
    });

    // Función para construir y mostrar la tabla de resultados
    function generateTable({ columns, data, summary }) {
        if (!data || data.length === 0) {
            resultsContent.innerHTML = `<div class="p-4 text-sm text-blue-700 bg-blue-100 rounded-lg">No se encontraron resultados para los criterios seleccionados.</div>`;
            return;
        }

        let tableHTML = `<div class="overflow-x-auto border dark:border-gray-700 rounded-lg">`;
        tableHTML += `<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">`;
        tableHTML += `<thead class="text-xs text-gray-700 dark:text-gray-300 uppercase bg-gray-50 dark:bg-gray-700"><tr>`;
       
        const columnKeys = Object.values(columns);
        for (const header in columns) {
            tableHTML += `<th scope="col" class="px-6 py-3">${header}</th>`;
        }
        tableHTML += `</tr></thead><tbody>`;

        data.forEach(row => {
            tableHTML += `<tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">`;
            columnKeys.forEach(key => {
                let cellData = row[key] || '';
                // Formatear la celda si es el total de una venta
                if (key === 'total_venta') {
                    cellData = `Bs. ${parseFloat(cellData).toFixed(2)}`; // Cambiado a Bs.
                }
                tableHTML += `<td class="px-6 py-4">${htmlspecialchars(cellData)}</td>`;
            });
            tableHTML += `</tr>`;
        });

        tableHTML += `</tbody></table></div>`;
       
        if (summary) {
            tableHTML += `<div class="mt-4 p-4 text-sm font-semibold text-gray-800 bg-gray-100 rounded-lg dark:bg-gray-700 dark:text-gray-200">${summary}</div>`;
        }
       
        resultsContent.innerHTML = tableHTML;
    }
   
    // Función para escapar HTML y prevenir XSS
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return str;
        return str.replace(/[&<>""']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }
});
