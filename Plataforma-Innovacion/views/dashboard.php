<?php 
$pageTitle = 'Panel de Usuario';
$currentPage = 'dashboard';
include 'header.php';

// Asumiendo que tienes acceso a estos datos
$totalSurveys = isset($totalSurveys) ? $totalSurveys : 0; // Total de encuestas
$totalPrioritized = isset($totalPrioritized) ? $totalPrioritized : 0; // Total de ideas priorizadas

// Si no tienes estos datos, deberías calcularlos aquí o en el controlador
?>

<style>
    .dashboard-container {
        max-width: 100%;
        margin: 0 auto;
        margin-top: 80px;
        padding: 20px;
        transition: all 0.3s ease;
    }
    .stat-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }
    .sidebar-expanded .dashboard-container {
        margin-left: 250px;
        width: calc(100% - 250px);
    }
    .sidebar-collapsed .dashboard-container {
        margin-left: 60px;
        width: calc(100% - 60px);
    }
    
    @media (max-width: 768px) {
        .sidebar-expanded .dashboard-container,
        .sidebar-collapsed .dashboard-container {
            margin-left: 0;
            width: 100%;
        }
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        background-color: #f0f0f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
    }
    .stat-icon i {
        font-size: 24px;
        color: #333;
    }
    .stat-content {
        flex-grow: 1;
    }
    .stat-content h3 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }
    .stat-content p {
        margin: 5px 0 15px;
        color: #666;
        font-size: 14px;
    }
    .stat-content .btn {
        width: 100%;
        padding: 10px;
        border: none;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-create { background-color: #002060; }
    .btn-view { background-color: #5F6368; }
    .btn-rate { background-color: #EC6F17; }
    .btn-prioritize { background-color: #FBBC05; }
    .btn-stats { background-color: #025E73; }
    .btn-ranking { background-color: #4CAF50; }
    .btn-sesion { background-color: #EC6F17; }
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
    }
    .total-item {
        text-align: center;
    }
    .total-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #025E73;
    }
    .total-label {
        font-size: 1rem;
        color: #666;
    }
</style>

<div class="dashboard-container">
    <div class="row">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="stat-content">
                    <h3>Hacer Ideas Creativas</h3>
                    <p>Crear una nueva idea</p>
                    <a href="index.php?action=survey" class="btn btn-create">Ir a crear</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="stat-content">
                    <h3>Ver Ideas Creativas</h3>
                    <p>Explorar ideas existentes</p>
                    <a href="index.php?action=listSurveys" class="btn btn-view">Ver ideas</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3>Calificar Ideas Creativas</h3>
                    <p>Evaluar ideas existentes</p>
                    <a href="index.php?action=rateSurveys" class="btn btn-rate">Calificar</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sort-amount-up"></i>
                </div>
                <div class="stat-content">
                    <h3>Priorizar Ideas Creativas</h3>
                    <p>Establecer prioridades</p>
                    <a href="index.php?action=prioritizeSurveys" class="btn btn-prioritize">Priorizar</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3>Ver Ranking</h3>
                    <p>Explorar el ranking de ideas</p>
                    <a href="index.php?action=viewRanking" class="btn btn-ranking">Ver ranking</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-content">
                    <h3>Ver Estadísticas</h3>
                    <p>Analizar datos de ideas</p>
                    <a href="index.php?action=surveyStats" class="btn btn-stats">Ver estadísticas</a>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-content">
                    <h3>Sesión de ideación</h3>
                    <p>Visualizar información detallada</p>
                    <a href="index.php?action=database" class="btn btn-primary w-100" style="background-color: #EC6F17;">Ver Base de Datos</a>
                </div>
            </div>
        </div>
    </div>
    <div class="totals-section">
        <div class="totals-container">
            <div class="total-item">
                <div class="total-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="total-number"><?php echo $totalSurveys; ?></div>
                <div class="total-label">Total de Encuestas</div>
            </div>
            <div class="total-item">
                <div class="total-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="total-number"><?php echo $totalPrioritized; ?></div>    
                <div class="total-label">Ideas Priorizadas</div>
            </div>
        </div>
    </div>

</div> <!-- Cierre de main-content -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const dashboardContainer = document.querySelector('.dashboard-container');
    const body = document.body;

    function updateLayout() {
        if (sidebar.classList.contains('expanded')) {
            body.classList.add('sidebar-expanded');
            body.classList.remove('sidebar-collapsed');
        } else {
            body.classList.add('sidebar-collapsed');
            body.classList.remove('sidebar-expanded');
        }
    }

    // Ejecutar inicialmente para establecer el estado correcto
    updateLayout();

    // Observar cambios en la clase del sidebar
    const observer = new MutationObserver(updateLayout);
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>