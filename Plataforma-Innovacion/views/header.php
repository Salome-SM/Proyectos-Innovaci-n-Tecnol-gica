<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Ideas Creativas'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #025E73;
            --secondary-color: #FFC000;
            --accent-color: #EC6F17;
            --text-color: #333;
            --bg-color: #f0f2f5;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 60px;
            background-color: white;
            padding-top: 60px;
            transition: width 0.3s;
            z-index: 1000;
        }

        .sidebar.expanded {
            width: 250px;
        }

        .sidebar .nav-link {
            color: var(--text-color);
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .sidebar .nav-link i {
            font-size: 1.2em;
        }

        .sidebar.expanded .nav-link {
            justify-content: flex-start;
            padding: 10px 20px;
            margin: 5px 15px;
        }

        .sidebar.expanded .nav-link i {
            margin-right: 10px;
        }

        .sidebar .nav-link span {
            display: none;
        }

        .sidebar.expanded .nav-link span {
            display: inline;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .top-bar {
            position: fixed;
            top: 0;
            left: 60px;
            right: 0;
            height: 60px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1001;
            transition: left 0.3s;
        }

        .top-bar.shifted {
            left: 250px;
        }

        .brand-name {
            font-size: 1.5em;
            font-weight: bold;
        }

        .brand-name .blue-text {
            color: #025E73;
        }

        .brand-name .orange-text {
            color: #EC6F17;
        }

        .top-right {
            display: flex;
            align-items: center;
        }

        .top-logo {
            height: 40px;
            margin-right: 15px;
        }

        .logout-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: #D15A0A;
            color: white;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--primary-color);
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="top-bar" id="topBar">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="brand-name">
            <span class="blue-text">Ideas</span> <span class="orange-text">Creativas</span>
        </div>
        <div class="top-right">
            <img src="images/Logo_Sin_Fondo.png" alt="Logo" class="top-logo">
            <a href="#" class="logout-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">Cerrar Sesión</a>
        </div>
    </div>

    <nav class="sidebar" id="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php?action=dashboard">
                    <i class="fas fa-home"></i> <span>Panel Principal</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'survey' ? 'active' : ''; ?>" href="index.php?action=survey">
                    <i class="fas fa-poll"></i> <span>Hacer Encuesta</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'listSurveys' ? 'active' : ''; ?>" href="index.php?action=listSurveys">
                    <i class="fas fa-lightbulb"></i> <span>Ver ideas creativas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'rateSurveys' ? 'active' : ''; ?>" href="index.php?action=rateSurveys">
                    <i class="fas fa-star"></i> <span>Calificar ideas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'prioritizeSurveys' ? 'active' : ''; ?>" href="index.php?action=prioritizeSurveys">
                    <i class="fas fa-sort-amount-up"></i> <span>Priorizar ideas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'viewRanking' ? 'active' : ''; ?>" href="index.php?action=viewRanking">
                    <i class="fas fa-trophy"></i> <span>Ver Ranking</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'surveyStats' ? 'active' : ''; ?>" href="index.php?action=surveyStats">
                    <i class="fas fa-chart-bar"></i> <span>Ver Estadísticas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'database' ? 'active' : ''; ?>" href="index.php?action=database">
                    <i class="fas fa-database"></i> <span>Sesión de ideación</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Modal de confirmación de cierre de sesión -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirmar cierre de sesión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que quieres cerrar sesión?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="index.php?action=logout" class="btn btn-primary">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const topBar = document.getElementById('topBar');
            const sidebarToggle = document.getElementById('sidebarToggle');

            function saveSidebarState(isExpanded) {
                localStorage.setItem('sidebarExpanded', isExpanded);
            }

            function loadSidebarState() {
                return localStorage.getItem('sidebarExpanded') === 'true';
            }

            function toggleSidebar() {
                const isExpanded = sidebar.classList.toggle('expanded');
                topBar.classList.toggle('shifted');
                saveSidebarState(isExpanded);
                
                // Disparar un evento personalizado
                document.dispatchEvent(new CustomEvent('sidebarToggle', { 
                    detail: { isExpanded: isExpanded }
                }));
            }

            // Set initial state
            if (loadSidebarState()) {
                sidebar.classList.add('expanded');
                topBar.classList.add('shifted');
                document.dispatchEvent(new CustomEvent('sidebarToggle', { 
                    detail: { isExpanded: true }
                }));
            }

            sidebarToggle.addEventListener('click', toggleSidebar);
        });
    </script>