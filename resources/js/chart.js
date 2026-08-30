import Chart from 'chart.js/auto';

export default function criarGrafico(canvas, tipo, rotulos, valores, titulo = '') {
    // O Chart.js não limpa o canvas sozinho; destrói o gráfico anterior antes de desenhar
    if (canvas._chart) {
        canvas._chart.destroy();
    }

    canvas._chart = new Chart(canvas.getContext('2d'), {
        type: tipo,
        data: {
            labels: rotulos,
            datasets: [
                {
                    label: titulo,
                    data: valores,
                    backgroundColor: [
                        '#0ea5e9', '#f59e0b', '#10b981', '#ef4444',
                        '#8b5cf6', '#14b8a6', '#f97316', '#64748b',
                        '#e11d48', '#84cc16',
                    ],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                // Sem legenda nos gráficos de uma série só (barra e linha)
                legend: { display: tipo !== 'bar' && tipo !== 'line' },
            },
        },
    });
}
