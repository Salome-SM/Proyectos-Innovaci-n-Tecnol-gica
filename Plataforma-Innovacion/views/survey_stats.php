<?php
$pageTitle = 'Panel de Distribución de Encuestas';
$currentPage = 'surveyStats';
include 'header.php';
if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<style>
    body {
        background-color: #f0f2f5;
        font-family: Arial, sans-serif;
    }

    .dashboard-container {
        padding: 20px;
        margin-top: 80px;
        transition: margin-left 0.3s ease, width 0.3s ease;
    }

    body.sidebar-expanded .dashboard-container {
        margin-left: 280px;
        width: calc(100% - 280px);
    }

    body.sidebar-collapsed .dashboard-container {
        margin-left: 90px;
        width: calc(100% - 90px);
    }

    .dashboard-card {
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        padding: 20px;
        height: 100%;
        transition: all 0.3s ease;
    }

    .metric-card {
        text-align: center;
        padding: 20px;
    }

    .metric-value {
        font-size: 2.5em;
        font-weight: bold;
        color: #002060;
        margin-bottom: 10px;
    }

    .metric-label {
        color: #025E73;
        font-size: 0.9em;
        font-weight: 500;
    }

    .chart-title {
        font-size: 1.2em;
        font-weight: bold;
        margin-bottom: 15px;
        color: #002060;
        text-align: center;
    }

    h1 {
        color: #002060;
        font-size: 1.8em;
        margin-bottom: 20px;
        text-align: center;
        font-weight: bold;
    }

    .chart-container {
        position: relative;
        height: 400px;
        width: 100%;
        margin: 20px 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: transparent;
        font-size: 0.9em;
    }

    .table th {
        background-color: #002060;
        color: white;
        padding: 12px;
        font-weight: 500;
    }

    .table td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #dee2e6;
    }

    .table tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 32, 96, 0.05);
    }

    .table tbody tr:hover {
        background-color: rgba(2, 94, 115, 0.1);
    }

    .totals-section {
        margin-top: 30px;
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .totals-container {
        display: flex;
        justify-content: space-around;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .total-item {
        text-align: center;
        padding: 15px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        flex: 1;
        min-width: 200px;
    }

    .total-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #025E73;
        margin-bottom: 10px;
    }

    .total-label {
        font-size: 1rem;
        color: #666;
    }

    .total-icon {
        font-size: 1.5em;
        color: #025E73;
        margin-bottom: 10px;
    }

    /* Distribución de puntos */
    .points-distribution {
        margin-top: 30px;
    }

    .points-bar {
        height: 25px;
        border-radius: 5px;
        margin-bottom: 10px;
        transition: width 0.3s ease;
    }

    .points-ideas { background-color: #90EE90; }
    .points-participation { background-color: #FFB347; }
    .points-impact { background-color: #FFB6B9; }

    /* Responsive design */
    @media (max-width: 1200px) {
        .metric-value {
            font-size: 2em;
        }

        .chart-container {
            height: 350px;
        }
    }

    @media (max-width: 992px) {
        .total-number {
            font-size: 2rem;
        }

        .chart-container {
            height: 300px;
        }
    }

    @media (max-width: 768px) {
        body.sidebar-expanded .dashboard-container,
        body.sidebar-collapsed .dashboard-container {
            margin-left: 0;
            width: 100%;
        }

        .dashboard-container {
            padding: 10px;
            margin-top: 60px;
        }

        .metric-value {
            font-size: 1.8em;
        }

        .chart-container {
            height: 250px;
        }

        .total-item {
            min-width: 150px;
        }

        h1 {
            font-size: 1.5em;
        }

        .chart-title {
            font-size: 1em;
        }

        .table {
            font-size: 0.8em;
        }
    }

    @media (max-width: 576px) {
        .dashboard-card {
            padding: 15px;
        }

        .metric-value {
            font-size: 1.5em;
        }

        .total-number {
            font-size: 1.8rem;
        }

        .total-label {
            font-size: 0.9rem;
        }
    }

    /* Transiciones suaves */
    .dashboard-container,
    .dashboard-card,
    .metric-value,
    .chart-container,
    .table,
    .total-item {
        transition: all 0.3s ease;
    }

    /* Estilos específicos para gráficos */
    .recharts-wrapper {
        margin: 0 auto;
    }

    /* Estilos para tooltips de gráficos */
    .recharts-tooltip-wrapper {
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 10px;
    }

    /* Leyendas de gráficos */
    .recharts-legend-item {
        padding: 5px 10px;
    }

    .recharts-legend-item-text {
        color: #333;
        font-size: 0.9em;
    }

    /* Animaciones */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .dashboard-card {
        animation: fadeIn 0.5s ease-in-out;
    }
</style>

<div class="dashboard-container" id="dashboardContainer">
    <h1>Panel de Distribución de Encuestas - <?php echo date('Y'); ?></h1>
    
    <div class="row g-3">
        <!-- Métricas clave -->
        <div class="col-md-3 col-sm-6">
            <div class="dashboard-card metric-card">
                <div class="metric-value" id="totalEncuestas">0</div>
                <div class="metric-label">Número de Encuestas</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="dashboard-card metric-card">
                <div class="metric-value" id="totalIdeas">0</div>
                <div class="metric-label">Total de Ideas</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="dashboard-card metric-card">
                <div class="metric-value" id="totalRetos">0</div>
                <div class="metric-label">Total de Retos</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="dashboard-card metric-card">
                <div class="metric-value" id="totalProblemas">0</div>
                <div class="metric-label">Total de Problemas</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <!-- Gráficos -->
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="chart-title">Distribución de Ideas/Retos/Problemas</div>
                <div class="chart-container">
                    <canvas id="chartDistribucion"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="chart-title">Ideas/Retos/Problemas por Área</div>
                <div class="chart-container">
                    <canvas id="chartPorArea"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="chart-title">Top 5 Contribuyentes</div>
                <div class="chart-container">
                    <canvas id="chartTopContribuyentes"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="chart-title">Resumen de Datos</div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm" id="dataTable">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Área</th>
                                <th>Ideas</th>
                                <th>Retos</th>
                                <th>Problemas</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="chart-title">Distribución de Iniciativas por Sede</div>
                <div class="chart-container">
                    <canvas id="chartPorSede"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-card">
                <div class="chart-title">Detalle de Encuestas por Sede</div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm" id="sedeTable">
                        <thead>
                            <tr>
                                <th>Sede</th>
                                <th>Total Encuestas</th>
                                <th>Ideas</th>
                                <th>Retos</th>
                                <th>Problemas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statsBySede as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['sede']); ?></td>
                                <td><?php echo $stat['total_encuestas']; ?></td>
                                <td><?php echo $stat['ideas']; ?></td>
                                <td><?php echo $stat['retos']; ?></td>
                                <td><?php echo $stat['problemas']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row g-3 mt-3">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="chart-title">Top 10 - Distribución de Puntos por Usuario</div>
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="pointsDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
let surveyData = [];
const chartColors = ['#002060', '#FFC000', '#EC6F17', '#025E73'];

// Funciones para el manejo del sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const dashboardContainer = document.getElementById('dashboardContainer');
    const body = document.body;

    function updateLayout() {
        if (window.innerWidth > 768) {
            if (sidebar.classList.contains('expanded')) {
                dashboardContainer.style.marginLeft = '280px';
                dashboardContainer.style.width = 'calc(100% - 280px)';
                body.classList.add('sidebar-expanded');
                body.classList.remove('sidebar-collapsed');
            } else {
                dashboardContainer.style.marginLeft = '90px';
                dashboardContainer.style.width = 'calc(100% - 90px)';
                body.classList.add('sidebar-collapsed');
                body.classList.remove('sidebar-expanded');
            }
        } else {
            dashboardContainer.style.marginLeft = '15px';
            dashboardContainer.style.width = 'calc(100% - 30px)';
            body.classList.remove('sidebar-expanded', 'sidebar-collapsed');
        }
    }

    // Ejecutar inicialmente
    updateLayout();

    // Observar cambios en la clase del sidebar
    const observer = new MutationObserver(updateLayout);
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', updateLayout);

    // Escuchar el evento del toggle del sidebar
    document.getElementById('sidebarToggle').addEventListener('click', updateLayout);

    // Inicializar las funciones de los gráficos
    initializeCharts();
});

// Funciones para las métricas y gráficos
function updateMetrics(data) {
    const total = data.length;
    const totalIdeas = data.reduce((sum, item) => sum + item.ideas, 0);
    const totalRetos = data.reduce((sum, item) => sum + item.retos, 0);
    const totalProblemas = data.reduce((sum, item) => sum + item.problemas, 0);

    document.getElementById('totalEncuestas').textContent = total;
    document.getElementById('totalIdeas').textContent = totalIdeas;
    document.getElementById('totalRetos').textContent = totalRetos;
    document.getElementById('totalProblemas').textContent = totalProblemas;
}

function createPieChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: chartColors
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

function createBarChart(canvasId, labels, data, stacked = true) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ideas',
                data: data.map(d => d.ideas),
                backgroundColor: chartColors[0]
            }, {
                label: 'Retos',
                data: data.map(d => d.retos),
                backgroundColor: chartColors[1]
            }, {
                label: 'Problemas',
                data: data.map(d => d.problemas),
                backgroundColor: chartColors[2]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: stacked,
                },
                y: {
                    stacked: stacked
                }
            }
        }
    });
}

function updateDataTable(data) {
    const tableBody = document.querySelector('#dataTable tbody');
    tableBody.innerHTML = '';
    data.forEach(item => {
        const row = tableBody.insertRow();
        row.insertCell(0).textContent = item.name;
        row.insertCell(1).textContent = item.area;
        row.insertCell(2).textContent = item.ideas;
        row.insertCell(3).textContent = item.retos;
        row.insertCell(4).textContent = item.problemas;
    });
}

function createPointsDistributionChart() {
    const ctx = document.getElementById('pointsDistributionChart').getContext('2d');
    const pointsData = <?php echo json_encode($pointsStats ?? []); ?>;
    
    const labels = pointsData.map(item => item.name);
    const ideasData = pointsData.map(item => parseInt(item.ideas_count));
    const participationData = pointsData.map(item => parseInt(item.participation_points));
    const impactData = pointsData.map(item => parseInt(item.impact_points));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ideas',
                    data: ideasData,
                    backgroundColor: '#90EE90',
                    borderColor: '#90EE90',
                    borderWidth: 1
                },
                {
                    label: 'Participación',
                    data: participationData,
                    backgroundColor: '#FFB347',
                    borderColor: '#FFB347',
                    borderWidth: 1
                },
                {
                    label: 'Impacto',
                    data: impactData,
                    backgroundColor: '#FFB6B9',
                    borderColor: '#FFB6B9',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Distribución de Puntos por Usuario (Top 10)'
                },
                tooltip: {
                    callbacks: {
                        afterBody: function(context) {
                            const index = context[0].dataIndex;
                            const total = ideasData[index] + participationData[index] + impactData[index];
                            return `Total de puntos: ${total}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            hover: {
                mode: 'nearest',
                intersect: true
            }
        }
    });
}

function initializeCharts() {
    // Cargar datos y crear gráficos
    fetch('api_surveys.php')
        .then(response => response.json())
        .then(data => {
            surveyData = data;
            updateMetrics(data);

            // Distribución total
            const totalIdeas = data.reduce((sum, item) => sum + item.ideas, 0);
            const totalRetos = data.reduce((sum, item) => sum + item.retos, 0);
            const totalProblemas = data.reduce((sum, item) => sum + item.problemas, 0);
            createPieChart('chartDistribucion', 
                        ['Ideas', 'Retos', 'Problemas'],
                        [totalIdeas, totalRetos, totalProblemas]);

            // Por área
            const areaData = data.reduce((acc, item) => {
                if (!acc[item.area]) acc[item.area] = {ideas: 0, retos: 0, problemas: 0};
                acc[item.area].ideas += item.ideas;
                acc[item.area].retos += item.retos;
                acc[item.area].problemas += item.problemas;
                return acc;
            }, {});
            createBarChart('chartPorArea', 
                        Object.keys(areaData),
                        Object.values(areaData));

            // Top 5 contribuyentes
            const topContribuyentes = data
                .map(item => ({
                    name: item.name,
                    ideas: item.ideas,
                    retos: item.retos,
                    problemas: item.problemas,
                    total: item.ideas + item.retos + item.problemas
                }))
                .sort((a, b) => b.total - a.total)
                .slice(0, 5);
            createBarChart('chartTopContribuyentes',
                        topContribuyentes.map(c => c.name),
                        topContribuyentes,
                        false);

            // Actualizar tabla de datos
            updateDataTable(data);

            // Nuevo gráfico por sede
            const sedeData = <?php echo json_encode($statsBySede); ?>;
            createBarChart('chartPorSede', 
                        sedeData.map(item => item.sede),
                        sedeData.map(item => ({
                            ideas: parseInt(item.ideas),
                            retos: parseInt(item.retos),
                            problemas: parseInt(item.problemas)
                        })));

            // Crear gráfico de distribución de puntos
            createPointsDistributionChart();
        })
        .catch(error => {
            console.error("Error fetching data:", error);
        });
}
</script>
</body>
</html>