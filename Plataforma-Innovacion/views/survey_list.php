<?php 
$pageTitle = 'Ver Base de Datos';
$currentPage = 'listSurveys';
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
        width: calc(100% - 60px);
        margin-left: 90px;
    }

    h2 {
        color: #025E73;
        text-align: center;
        margin-bottom: 30px;
        font-weight: bold;
        font-size: 2.0rem;
    }

    .table-container {
        overflow-x: auto;
        margin: 0 3px;
        padding: 0 3px;
        border-radius: 10px;
    }

    .table {
        width: calc(100% - 10px);
        margin: 0 auto;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #ddd;
        border-radius: 10px;
        overflow: hidden;
        table-layout: fixed;
        font-size: 13px;
        color: #333333;
    }

    .table thead th {
        background-color: #025E73;
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.6px;
        border: 1px solid #025E73;
        text-align: center;
        padding: 12px;
        position: sticky;
        top: 0;
        z-index: 10;
        text-shadow: 1px 1px 1px rgba(0,0,0,0.2);
    }

    .table tbody tr:nth-of-type(even) {
        background-color: #f8f9fa;
    }

    .table tbody tr:hover {
        background-color: rgba(2, 94, 115, 0.1);
        transition: background-color 0.3s ease;
    }

    .table tbody tr {
        line-height: 1.4;
    }

    .table th, .table td {
        text-align: center;
        padding: 12px;
        vertical-align: middle;
        border: 1px solid #ddd;
        font-size: 13px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .table tbody td {
        font-weight: 400;
    }

    /* Column widths */
    .table th:nth-child(1), .table td:nth-child(1) { width: 8%; }  /* Fecha */
    .table th:nth-child(2), .table td:nth-child(2) { width: 10%; } /* Nombre */
    .table th:nth-child(3), .table td:nth-child(3) { width: 8%; }  /* Cédula */
    .table th:nth-child(4), .table td:nth-child(4) { width: 5%; }  /* Sede */
    .table th:nth-child(5), .table td:nth-child(5) { width: 8%; } /* Área */
    .table th:nth-child(6), .table td:nth-child(6) { width: 8%; } /* Tipo */
    .table th:nth-child(7), .table td:nth-child(7) { width: 11%; } /* Oportunidad */
    .table th:nth-child(8), .table td:nth-child(8) { width: 9%; } /* Impacto */
    .table th:nth-child(9), .table td:nth-child(9) { width: 25%; } /* Detalles */
    .table th:nth-child(10), .table td:nth-child(10) { width: 8%; } /* Archivo */

    .details-cell {
        text-align: left;
        max-width: 300px;
        white-space: normal;
        word-wrap: break-word;
        font-size: 13px;
        padding: 15px;
        line-height: 1.5;
    }

    .details-cell strong {
        display: block;
        color: #025E73;
        margin-top: 5px;
        margin-bottom: 3px;
        font-weight: 600;
        font-size: 13.5px;
    }

    .details-cell span {
        display: block;
        margin-bottom: 4px;
        color: #333333;
        line-height: 1.4;
    }

    .btn-download-pdf, 
    .btn-filter, 
    .btn-attachment {
        background-color: #EC6F17;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        font-size: 13px;
    }

    .btn-download-pdf:hover, 
    .btn-filter:hover, 
    .btn-attachment:hover {
        background-color: #D15A0A;
        color: white;
        text-decoration: none;
    }

    .filter-container {
        margin: 0 20px 20px 20px;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
    }

    .form-select {
        border: 1px solid #025E73;
        border-radius: 5px;
        padding: 8px;
        font-size: 13px;
        width: 100%;
    }

    .form-select:focus {
        border-color: #EC6F17;
        box-shadow: 0 0 0 0.2rem rgba(236, 111, 23, 0.25);
        outline: none;
    }

    .pdf-container {
        margin: 0 20px 20px 20px;
    }

    .btn-download-pdf i {
        margin-right: 5px;
    }

    .filters-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }

    .filter-group {
        flex: 1 1 200px;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        color: #025E73;
        font-weight: bold;
        font-size: 13px;
    }

    .button-group {
        display: flex;
        align-items: flex-end;
    }

    .no-attachment {
        color: #6c757d;
        font-style: italic;
        font-size: 13px;
    }

    .sidebar-expanded .container {
        margin-left: 280px;
        width: calc(100% - 280px - 30px);
        max-width: 1500px;
    }

    .sidebar-collapsed .container {
        margin-left: 90px;
        width: calc(100% - 90px - 30px);
        max-width: 1500px;
    }

    /* Responsive Media Queries */
    @media (max-width: 1460px) {
        .sidebar-expanded .container {
            margin-left: 280px;
            width: calc(100% - 280px - 15px);
        }
        
        .sidebar-collapsed .container {
            margin-left: 90px;
            width: calc(100% - 90px - 15px);
        }
    }

    @media (max-width: 1200px) {
        .table,
        .table thead th,
        .table th, 
        .table td,
        .details-cell,
        .btn-download-pdf, 
        .btn-filter, 
        .btn-attachment,
        .form-select {
            font-size: 12px;
        }

        .details-cell strong {
            font-size: 12.5px;
        }
    }

    @media (max-width: 992px) {
        .table,
        .table thead th,
        .table th, 
        .table td,
        .details-cell,
        .btn-download-pdf, 
        .btn-filter, 
        .btn-attachment,
        .form-select {
            font-size: 11px;
        }

        .details-cell strong {
            font-size: 11.5px;
        }

        .table th, .table td {
            padding: 10px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
            margin-top: 20px;
            width: calc(100% - 30px);
            margin-left: 15px;
            margin-right: 15px;
        }

        h2 {
            font-size: 1.75rem;
        }

        .table-container,
        .filter-container,
        .pdf-container {
            margin: 0 10px;
            padding: 0 10px;
        }

        .table {
            width: calc(100% - 20px);
        }

        .table,
        .table thead th,
        .table th, 
        .table td,
        .details-cell,
        .btn-download-pdf, 
        .btn-filter, 
        .btn-attachment,
        .form-select {
            font-size: 10px;
        }

        .details-cell strong {
            font-size: 10.5px;
        }

        .table th, .table td {
            padding: 8px;
        }

        .form-select,
        .btn-filter {
            margin-bottom: 10px;
        }

        .container,
        .sidebar-expanded .container,
        .sidebar-collapsed .container {
            margin-left: auto;
            margin-right: auto;
            width: calc(100% - 30px);
            max-width: 100%;
        }
    }
</style>

<div class="container">
    <h2>Visualización</h2>
    
    <div class="mb-4">
        <a href="index.php?action=downloadPDF" class="btn btn-download-pdf">
            <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
        </a>
    </div>

    <form action="index.php?action=listSurveys" method="GET" class="mb-4">
        <input type="hidden" name="action" value="listSurveys">
        <div class="row g-3">
            <div class="col-md-2">
                <select name="sede" class="form-select">
                    <option value="">Todas las sedes</option>
                    <?php foreach ($sedes as $sede): ?>
                        <option value="<?php echo htmlspecialchars($sede); ?>" <?php echo isset($_GET['sede']) && $_GET['sede'] == $sede ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sede); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="area" class="form-select">
                    <option value="">Todas las áreas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo htmlspecialchars($area); ?>" <?php echo isset($_GET['area']) && $_GET['area'] == $area ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="initiative_type" class="form-select">
                    <option value="">Todos los tipos</option>
                    <option value="idea" <?php echo isset($_GET['initiative_type']) && $_GET['initiative_type'] == 'idea' ? 'selected' : ''; ?>>Idea</option>
                    <option value="problema" <?php echo isset($_GET['initiative_type']) && $_GET['initiative_type'] == 'problema' ? 'selected' : ''; ?>>Problema</option>
                    <option value="reto" <?php echo isset($_GET['initiative_type']) && $_GET['initiative_type'] == 'reto' ? 'selected' : ''; ?>>Reto</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="oportunidad" class="form-select">
                    <option value="">Todas las oportunidades</option>
                    <?php foreach ($oportunidades as $oportunidad): ?>
                        <option value="<?php echo htmlspecialchars($oportunidad); ?>" <?php echo isset($_GET['oportunidad']) && $_GET['oportunidad'] == $oportunidad ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($oportunidad); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="impacto" class="form-select">
                    <option value="">Todos los impactos</option>
                    <?php foreach ($impactos as $impacto): ?>
                        <option value="<?php echo htmlspecialchars($impacto); ?>" <?php echo isset($_GET['impacto']) && $_GET['impacto'] == $impacto ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($impacto); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-filter w-100">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Sede</th>
                    <th>Área</th>
                    <th>Iniciativa</th>
                    <th>Oportunidad</th>
                    <th>Impacto</th>
                    <th>Detalles</th>
                    <th>Archivo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($surveys as $survey): ?>
                <tr>
                    <td><?php echo $survey['fecha_ingreso'] ? date('d/m/Y', strtotime($survey['fecha_ingreso'])) : ''; ?></td>
                    <td><?php echo htmlspecialchars($survey['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($survey['cedula'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($survey['sede'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($survey['area'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($survey['initiative_type'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($survey['oportunidad'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($survey['impacto'] ?? ''); ?></td>
                    <td class="details-cell">
                        <?php
                        if (isset($survey['specific_data'])) {
                            $specificData = json_decode($survey['specific_data'], true);
                            if (is_array($specificData)) {
                                $nombre = '';
                                $descripcion = '';
                                
                                switch ($survey['initiative_type']) {
                                    case 'idea':
                                        $nombre = $specificData['nombreIdea'] ?? '';
                                        $descripcion = $specificData['descripcionIdea'] ?? '';
                                        break;
                                    case 'problema':
                                        $nombre = $specificData['nombreProblema'] ?? '';
                                        $descripcion = $specificData['descripcionProblema'] ?? '';
                                        break;
                                    case 'reto':
                                        $nombre = $specificData['nombreReto'] ?? '';
                                        $descripcion = $specificData['descripcionReto'] ?? '';
                                        break;
                                }
                                
                                if (!empty($nombre)) {
                                    echo "<strong>Nombre:</strong> <span>" . htmlspecialchars($nombre) . "</span>";
                                }
                                if (!empty($descripcion)) {
                                    echo "<strong>Descripción:</strong> <span>" . htmlspecialchars($descripcion) . "</span>";
                                }
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($survey['attachment_path'])) {
                            echo '<a href="' . htmlspecialchars($survey['attachment_path']) . '" target="_blank" class="btn btn-attachment"><i class="fas fa-eye mr-2"></i> Ver</a>';
                        } else {
                            echo 'Sin adjunto';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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
                body.classList.add('sidebar-expanded');
                body.classList.remove('sidebar-collapsed');
            } else {
                body.classList.add('sidebar-collapsed');
                body.classList.remove('sidebar-expanded');
            }
        } else {
            body.classList.remove('sidebar-expanded', 'sidebar-collapsed');
        }
    }

    // Ejecutar inicialmente y en cada cambio de tamaño de ventana
    updateLayout();
    window.addEventListener('resize', updateLayout);

    // Observar cambios en la clase del sidebar
    const observer = new MutationObserver(updateLayout);
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
});
function toggleDetails(button) {
    var cell = button.closest('.details-cell');
    cell.classList.toggle('expanded');
    button.textContent = cell.classList.contains('expanded') ? 'Menos' : 'Más';
}
</script>

</body>
</html>