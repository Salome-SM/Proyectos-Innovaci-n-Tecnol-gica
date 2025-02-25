<?php 
$pageTitle = 'Calificar Base de Datos';
$currentPage = 'rateSurveys';
include 'header.php';
// Mapeo de nombres de campos en inglés a español
$fieldNameMapping = [
    'oportunidad' => 'Oportunidad',
    'impacto' => 'Impacto',
    'nombreIdea' => 'Nombre de la Idea',
    'descripcionIdea' => 'Descripción de la Idea',
    'nombreProblema' => 'Nombre del Problema',
    'descripcionProblema' => 'Descripción del Problema',
    'nombreReto' => 'Nombre del Reto',
    'descripcionReto' => 'Descripción del Reto'
];
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
        border: 1px solid #ffffff;
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
        background-color: #ffffff;
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

    /* Anchos de columna ajustados */
    .table th:nth-child(1), .table td:nth-child(1) { width: 8%; }   /* Fecha */
    .table th:nth-child(2), .table td:nth-child(2) { width: 10%; }  /* Nombre */
    .table th:nth-child(3), .table td:nth-child(3) { width: 8%; }   /* Cédula */
    .table th:nth-child(4), .table td:nth-child(4) { width: 5%; }   /* Sede */
    .table th:nth-child(5), .table td:nth-child(5) { width: 8%; }   /* Área */
    .table th:nth-child(6), .table td:nth-child(6) { width: 8%; }   /* Tipo */
    .table th:nth-child(7), .table td:nth-child(7) { width: 11%; }  /* Oportunidad */
    .table th:nth-child(8), .table td:nth-child(8) { width: 9%; }   /* Impacto */
    .table th:nth-child(9), .table td:nth-child(9) { width: 24%; }  /* Detalles */
    .table th:nth-child(10), .table td:nth-child(10) { width: 9%; } /* Acciones */

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
        color: #ffffff;
        line-height: 1.4;
    }

    /* Estilos de botones */
    .btn-primary, 
    .btn-filter,
    .btn-calificar {
        background-color: #EC6F17;
        border: none !important;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        transition: all 0.3s ease;
        outline: none !important;
        box-shadow: none !important;
        font-size: 13px;
    }

    .btn-primary:hover, 
    .btn-primary:focus,
    .btn-primary:active,
    .btn-filter:hover,
    .btn-filter:focus,
    .btn-filter:active,
    .btn-calificar:hover,
    .btn-calificar:focus,
    .btn-calificar:active {
        background-color: #D15A0A !important;
        border: none !important;
        color: white;
        outline: none !important;
        box-shadow: none !important;
    }

    .btn-primary.rated {
        background-color: #4CAF50 !important;
    }

    .btn-primary.rated:hover,
    .btn-primary.rated:focus,
    .btn-primary.rated:active {
        background-color: #45a049 !important;
    }

    /* Estilos de calificaciones */
    .rating-high {
        background-color: #90EE90;
        font-weight: bold;
        font-size: 13px;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .rating-medium {
        background-color: #FFB347;
        font-weight: bold;
        font-size: 13px;
        padding: 4px 8px;
        border-radius: 4px;
    }
    
    .rating-low {
        background-color: #FFB6B9;
        font-weight: bold;
        font-size: 13px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    /* Estilos de filtros */
    .filter-row {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 10px;
        background-color: #f8f9fa;
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

    /* Estilos del modal */
    .modal-content {
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background-color: #025E73;
        color: white;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        padding: 15px 20px;
    }

    .modal-title {
        color: white;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-body label {
        font-weight: bold;
        color: #002060;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #ffffff;
    }

    /* Estilos responsive */
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

    @media (max-width: 1460px) {
        body.sidebar-expanded .container {
            margin-left: 280px;
            width: calc(100% - 280px - 15px);
        }
        
        body.sidebar-collapsed .container {
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
        .btn-primary, 
        .btn-filter,
        .btn-calificar,
        .form-select,
        .modal-body label {
            font-size: 12px;
        }

        .details-cell strong {
            font-size: 12.5px;
        }

        .rating-high,
        .rating-medium,
        .rating-low {
            font-size: 12px;
            padding: 3px 6px;
        }
    }

    @media (max-width: 992px) {
        .table,
        .table thead th,
        .table th, 
        .table td,
        .details-cell,
        .btn-primary, 
        .btn-filter,
        .btn-calificar,
        .form-select,
        .modal-body label {
            font-size: 11px;
        }

        .details-cell strong {
            font-size: 11.5px;
        }

        .rating-high,
        .rating-medium,
        .rating-low {
            font-size: 11px;
        }

        .table th, .table td {
            padding: 10px;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
            margin-top: 20px;
            width: calc(100% - 30px) !important;
            margin-left: 15px !important;
            margin-right: 15px !important;
        }

        h2 {
            font-size: 1.75rem;
        }

        .table-container {
            margin: 0;
            padding: 0;
        }

        .table {
            width: 100%;
        }

        .table,
        .table thead th,
        .table th, 
        .table td,
        .details-cell,
        .btn-primary, 
        .btn-filter,
        .btn-calificar,
        .form-select,
        .modal-body label {
            font-size: 10px;
        }

        .details-cell strong {
            font-size: 10.5px;
        }

        .rating-high,
        .rating-medium,
        .rating-low {
            font-size: 10px;
            padding: 2px 4px;
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

        .modal-dialog {
            margin: 10px;
        }
    }
</style>

<div class="container">
    <h2>Calificación</h2>
    
    <div class="row filter-row">
        <div class="col-md-2">
            <select name="sede" class="form-select">
                <option value="">Todas las sedes</option>
                <?php foreach ($sedes as $sede): ?>
                    <option value="<?php echo htmlspecialchars($sede); ?>"><?php echo htmlspecialchars($sede); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="area" class="form-select">
                <option value="">Todas las áreas</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?php echo htmlspecialchars($area); ?>"><?php echo htmlspecialchars($area); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="initiative_type" class="form-select">
                <option value="">Todos los tipos</option>
                <option value="idea">Idea</option>
                <option value="problema">Problema</option>
                <option value="reto">Reto</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="oportunidad" class="form-select">
                <option value="">Todas las oportunidades</option>
                <?php foreach ($oportunidades as $oportunidad): ?>
                    <option value="<?php echo htmlspecialchars($oportunidad); ?>"><?php echo htmlspecialchars($oportunidad); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="impacto" class="form-select">
                <option value="">Todos los impactos</option>
                <?php foreach ($impactos as $impacto): ?>
                    <option value="<?php echo htmlspecialchars($impacto); ?>"><?php echo htmlspecialchars($impacto); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (empty($error) && !empty($uniqueSurveys)): ?>
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
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ordenar encuestas por calificación promedio
                    usort($uniqueSurveys, function($a, $b) {
                        $avgA = isset($a['average_rating']) ? floatval($a['average_rating']) : 0;
                        $avgB = isset($b['average_rating']) ? floatval($b['average_rating']) : 0;
                        return $avgB <=> $avgA; // Orden descendente
                    });

                    foreach ($uniqueSurveys as $survey): 
                        $averageRating = isset($survey['average_rating']) ? floatval($survey['average_rating']) : 0;
                        $ratingClass = '';
                        if ($averageRating >= 4) {
                            $ratingClass = 'rating-high';
                        } elseif ($averageRating >= 3) {
                            $ratingClass = 'rating-medium';
                        } elseif ($averageRating > 0) {
                            $ratingClass = 'rating-low';
                        }
                    ?>
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
                            $specificData = json_decode($survey['specific_data'], true);
                            if (isset($specificData['nombre' . ucfirst($survey['initiative_type'])])) {
                                echo "<strong>Nombre:</strong> " . htmlspecialchars($specificData['nombre' . ucfirst($survey['initiative_type'])]) . "<br>";
                            }
                            if (isset($specificData['descripcion' . ucfirst($survey['initiative_type'])])) {
                                echo "<strong>Descripción:</strong> " . htmlspecialchars($specificData['descripcion' . ucfirst($survey['initiative_type'])]);
                            }
                            ?>
                        </td>
                        <td class="rating-column">
                            <?php 
                            $userEmail = $_SESSION['email'] ?? '';
                            $hasRated = false;
                            
                            // Verificar si el usuario ya ha calificado esta encuesta
                            if (isset($survey['ratings']) && is_array($survey['ratings'])) {
                                foreach ($survey['ratings'] as $rating) {
                                    if ($rating['email'] === $userEmail) {
                                        $hasRated = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ($survey['canRate']): ?>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="openRatingModal(<?php echo $survey['id']; ?>)"
                                        style="<?php echo $hasRated ? 'background-color: #4CAF50;' : ''; ?>">
                                    <?php echo $hasRated ? 'Actualizar' : 'Calificar'; ?>
                                </button>
                            <?php else: ?>
                                <span class="not-authorized">No autorizado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (empty($error)): ?>
        <p>No hay encuestas disponibles para calificar en este momento.</p>
    <?php endif; ?>
</div>

<!-- Modal para calificación -->
<div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ratingModalLabel">Calificar Encuesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- El formulario se cargará aquí dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="submitRating()">Guardar Calificación</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentSurveyId;

function openRatingModal(surveyId) {
    currentSurveyId = surveyId;
    console.log('Survey ID:', surveyId); // Para depuración
    $.ajax({
        url: 'index.php?action=getRatingForm',
        method: 'GET',
        data: { surveyId: surveyId },
        success: function(response) {
            $('#ratingModal .modal-body').html(response);
            loadRatings(surveyId);
            var modal = new bootstrap.Modal(document.getElementById('ratingModal'));
            modal.show();
        },
        error: function() {
            alert('Error al cargar el formulario de calificación');
        }
    });
}

function loadRatings(surveyId) {
    $.ajax({
        url: 'index.php?action=getRatings',
        method: 'GET',
        data: { surveyId: surveyId },
        success: function(response) {
            $('#ratingsDisplay').html(response);
        },
        error: function() {
            alert('Error al cargar las calificaciones');
        }
    });
}

function submitRating() {
    var formData = $('#ratingForm').serialize();
    formData += '&surveyId=' + currentSurveyId;
    
    $.ajax({
        url: 'index.php?action=saveRating',
        method: 'POST',
        data: formData,
        dataType: 'json'
    })
    .done(function(response) {
        if (response.success) {
            // Cerrar el modal
            $('#ratingModal').modal('hide');
            
            // Actualizar la página para mostrar los cambios
            window.location.reload();
            
            // O alternativamente, actualizar solo el botón específico
            // const button = $(`button[onclick="openRatingModal(${currentSurveyId})"]`);
            // button.text('Actualizar');
            // button.css('background-color', '#4CAF50');
        } else {
            alert('Error al guardar la calificación: ' + response.message);
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
        console.log("Respuesta del servidor:", jqXHR.responseText);
        alert('Error al procesar la solicitud. Por favor, revise la consola para más detalles.');
    });
}

// Nuevo código para manejar el sidebar
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    
    function updateLayout(isExpanded) {
        if (isExpanded) {
            body.classList.remove('sidebar-collapsed');
            body.classList.add('sidebar-expanded');
        } else {
            body.classList.remove('sidebar-expanded');
            body.classList.add('sidebar-collapsed');
        }
    }

    // Estado inicial
    updateLayout(document.getElementById('sidebar').classList.contains('expanded'));

    // Escuchar el evento del sidebar
    document.addEventListener('sidebarToggle', function(e) {
        updateLayout(e.detail.isExpanded);
    });

    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            body.classList.remove('sidebar-expanded', 'sidebar-collapsed');
        } else {
            updateLayout(document.getElementById('sidebar').classList.contains('expanded'));
        }
    });
});
</script>
</body>
</html>