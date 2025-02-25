<?php 
$pageTitle = 'Base de Datos';
$currentPage = 'database';
include 'header.php';
?>

<style>
    .container {
        background-color: #ffffff;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-top: 80px;
        transition: all 0.3s ease;
        max-width: 1500px;
    }

    body.sidebar-expanded .container {
        margin-left: 280px;
        width: calc(100% - 280px - 30px);
    }

    body.sidebar-collapsed .container {
        margin-left: 90px;
        width: calc(100% - 90px - 30px);
    }

    h2 {
        color: #025E73;
        text-align: center;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-number {
        font-size: 2em;
        font-weight: bold;
        color: #025E73;
        margin: 10px 0;
    }

    .stat-label {
        color: #666;
        font-size: 0.9em;
    }

    .table-container {
        overflow-x: auto;
        margin-top: 20px;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
    }

    .custom-table th {
        background-color: #025E73;
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 500;
    }

    .custom-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }

    .custom-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .custom-table tbody tr:hover {
        background-color: rgba(2, 94, 115, 0.05);
    }

    .filters {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .filter-group {
        margin-bottom: 15px;
    }

    .filter-label {
        display: block;
        margin-bottom: 5px;
        color: #025E73;
        font-weight: 500;
    }

    .filter-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .export-buttons {
        margin-bottom: 20px;
    }

    .btn-export {
        background-color: #025E73;
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-right: 10px;
    }

    .btn-export:hover {
        background-color: #013d4d;
    }

    @media (max-width: 768px) {
        .container {
            margin-left: 15px !important;
            margin-right: 15px !important;
            width: calc(100% - 30px) !important;
            padding: 15px;
        }

        .stats-cards {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <h2>Sesión de ideación</h2>

    <div class="filters">
        <div class="row">
            <div class="col-md-3">
                <div class="filter-group">
                    <label class="filter-label">Buscar</label>
                    <input type="text" class="filter-input" placeholder="Buscar...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="filter-group">
                    <label class="filter-label">Área</label>
                    <select class="filter-input">
                        <option value="">Todas las áreas</option>
                        <!-- Agregar opciones dinámicamente -->
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="filter-group">
                    <label class="filter-label">Fecha</label>
                    <input type="date" class="filter-input">
                </div>
            </div>
            <div class="col-md-3">
                <div class="filter-group">
                    <label class="filter-label">Estado</label>
                    <select class="filter-input">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="export-buttons">
        <button class="btn-export"><i class="fas fa-file-excel"></i> Exportar Excel</button>
        <button class="btn-export"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
    </div>

    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Área</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Datos de ejemplo - Reemplazar con datos reales de la base de datos -->
                <tr>
                    <td>1</td>
                    <td>Ejemplo 1</td>
                    <td>Área 1</td>
                    <td>2024-01-01</td>
                    <td>Activo</td>
                    <td>
                        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <!-- Agregar más filas según sea necesario -->
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const container = document.querySelector('.container');
    const body = document.body;

    function updateLayout() {
        if (window.innerWidth > 768) {
            if (sidebar.classList.contains('expanded')) {
                body.classList.remove('sidebar-collapsed');
                body.classList.add('sidebar-expanded');
            } else {
                body.classList.remove('sidebar-expanded');
                body.classList.add('sidebar-collapsed');
            }
        } else {
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
});
</script>