<?php 
$pageTitle = 'Priorización de Encuestas';
$currentPage = 'prioritizeSurveys';
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
        font-size: 12px;
        color: #333333;
    }

    .table thead th {
        background-color: #025E73;
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
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
        font-size: 12px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .table tbody td {
        font-weight: 400;
    }

    /* Column widths */
    .table th:nth-child(1), .table td:nth-child(1) { width: 8%; }  /* Fecha */
    .table th:nth-child(2), .table td:nth-child(2) { width: 7%; } /* Nombre */
    .table th:nth-child(3), .table td:nth-child(3) { width: 5%; }  /* Sede */
    .table th:nth-child(4), .table td:nth-child(4) { width: 8%; } /* Tipo de Iniciativa */
    .table th:nth-child(5), .table td:nth-child(5) { width: 10%; } /* Oportunidad */
    .table th:nth-child(6), .table td:nth-child(6) { width: 9%; } /* Impacto */
    .table th:nth-child(7), .table td:nth-child(7) { width: 17%; } /* Detalles */
    .table th:nth-child(8), .table td:nth-child(8) { width: 9%; } /* Ponderado */
    .table th:nth-child(9), .table td:nth-child(9) { width: 11%; } /* Costo Aprox */
    .table th:nth-child(10), .table td:nth-child(10) { width: 16%; } /* Detalles de Calificación */

    .details-cell {
        text-align: left;
        max-width: 300px;
        white-space: normal;
        word-wrap: break-word;
        font-size: 12px;
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

    .rating-cards-container {
        overflow-x: auto;
        white-space: nowrap;
        max-height: 150px;
        padding: 10px 0;
    }

    .rating-card {
        display: inline-block;
        width: 200px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        background-color: #f9f9f9;
        margin-right: 10px;
    }

    .rating-card-header {
        background-color: #025E73;
        color: white;
        padding: 8px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        font-weight: bold;
        font-size: 0.9em;
    }

    .rating-card-body {
        padding: 10px;
    }

    .rating-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.85em;
    }

    .rating-label {
        font-weight: bold;
        color: #025E73;
    }

    .rating-high {
        background-color: #90EE90;
        padding: 5px;
        border-radius: 4px;
    }
    
    .rating-medium {
        background-color: #FFB347;
        padding: 5px;
        border-radius: 4px;
    }
    
    .rating-low {
        background-color: #FFB6B9;
        padding: 5px;
        border-radius: 4px;
    }

    .costo-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-edit-costo {
        background-color: #025E73;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
    }

    .btn-edit-costo:hover {
        background-color: #023E53;
    }

    .input-group-text {
        background-color: #f8f9fa;
        color: #025E73;
        font-weight: bold;
    }

    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
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

        .table-container {
            margin: 0 10px;
            padding: 0 10px;
        }

        .table {
            width: calc(100% - 20px);
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
    <h2>Priorización</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($surveysWithDetails)): ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Nombre</th>
                        <th>Sede</th>
                        <th>Iniciativa</th>
                        <th>Oportunidad</th>
                        <th>Impacto</th>
                        <th>Detalles</th>
                        <th>Ponderado</th>
                        <th>Costo Aprox</th>
                        <th>Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    usort($surveysWithDetails, function($a, $b) {
                        return $b['finalRating'] - $a['finalRating'];
                    });

                    foreach ($surveysWithDetails as $survey): 
                        $ratingClass = '';
                        if ($survey['finalRating'] >= 4) {
                            $ratingClass = 'rating-high';
                        } elseif ($survey['finalRating'] >= 3) {
                            $ratingClass = 'rating-medium';
                        } elseif ($survey['finalRating'] > 0) {
                            $ratingClass = 'rating-low';
                        }
                    ?>
                    <tr data-survey-id="<?php echo $survey['id']; ?>">
                        <td><?php echo date('d/m/Y', strtotime($survey['fecha_ingreso'])); ?></td>
                        <td><?php echo htmlspecialchars($survey['name']); ?></td>
                        <td><?php echo htmlspecialchars($survey['sede']); ?></td>
                        <td><?php echo htmlspecialchars($survey['initiative_type']); ?></td>
                        <td><?php echo htmlspecialchars($survey['oportunidad']); ?></td>
                        <td><?php echo htmlspecialchars($survey['impacto']); ?></td>
                        <td class="details-cell">
                            <?php
                            $specificData = json_decode($survey['specific_data'], true);
                            if (isset($specificData['nombre' . ucfirst($survey['initiative_type'])])) {
                                echo "<strong>Nombre:</strong> <span>" . htmlspecialchars($specificData['nombre' . ucfirst($survey['initiative_type'])]) . "</span>";
                            }
                            if (isset($specificData['descripcion' . ucfirst($survey['initiative_type'])])) {
                                echo "<strong>Descripción:</strong> <span>" . htmlspecialchars($specificData['descripcion' . ucfirst($survey['initiative_type'])]) . "</span>";
                            }
                            ?>
                        </td>
                        <td>
                            <span class="rating-value <?php echo $ratingClass; ?>">
                                <?php echo number_format($survey['finalRating'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($_SESSION['email'] === 'innovaciontr@gmail.com'): ?>
                                <div class="costo-container">
                                    <span class="costo-display">
                                        <?php echo isset($survey['costo_aprox']) ? '$ ' . number_format($survey['costo_aprox'], 0, ',', '.') : 'No definido'; ?>
                                    </span>
                                    <button class="btn btn-sm btn-edit-costo" onclick="editCosto(<?php echo $survey['id']; ?>, <?php echo $survey['costo_aprox'] ?? 'null'; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php echo isset($survey['costo_aprox']) ? '$ ' . number_format($survey['costo_aprox'], 0, ',', '.') : 'No definido'; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($survey['ratings'])): ?>
                                <div class="rating-cards-container">
                                    <?php foreach ($survey['ratings'] as $rating): 
                                        // Solo mostrar valores que no son nulos y están permitidos
                                        $valores = [];
                                        if ($rating['factible'] !== null && in_array('factible', $rating['campos_permitidos'])) {
                                            $valores['Factible'] = $rating['factible'];
                                        }
                                        if ($rating['viable'] !== null && in_array('viable', $rating['campos_permitidos'])) {
                                            $valores['Viable'] = $rating['viable'];
                                        }
                                        if ($rating['deseable'] !== null && in_array('deseable', $rating['campos_permitidos'])) {
                                            $valores['Deseable'] = $rating['deseable'];
                                        }
                                        if ($rating['impacta_estrategia'] !== null && in_array('impacta_estrategia', $rating['campos_permitidos'])) {
                                            $valores['Impacta estrategia'] = $rating['impacta_estrategia'];
                                        }

                                        $avgRating = !empty($valores) ? array_sum($valores) / count($valores) : 0;
                                        $ratingClass = '';
                                        if ($avgRating >= 4) {
                                            $ratingClass = 'rating-high';
                                        } elseif ($avgRating >= 3) {
                                            $ratingClass = 'rating-medium';
                                        } else {
                                            $ratingClass = 'rating-low';
                                        }
                                    ?>
                                        <div class="rating-card">
                                            <div class="rating-card-header">
                                                <?php echo htmlspecialchars($rating['email']); ?>
                                            </div>
                                            <div class="rating-card-body">
                                                <?php foreach ($valores as $nombre => $valor): ?>
                                                    <div class="rating-item">
                                                        <span class="rating-label"><?php echo $nombre; ?>:</span>
                                                        <span><?php echo number_format($valor, 2); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                                
                                                <div class="rating-item">
                                                    <span class="rating-label">Promedio:</span>
                                                    <span class="<?php echo $ratingClass; ?>">
                                                        <?php echo number_format($avgRating, 2); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>Sin calificaciones</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No hay encuestas disponibles para priorizar en este momento.</p>
    <?php endif; ?>
    <!-- Modal para edición de costo -->
    <div class="modal fade" id="costoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Costo Aproximado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editSurveyId">
                    <div class="mb-3">
                        <label class="form-label">Costo Aproximado (COP)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="editCostoAprox" class="form-control" min="0" step="1000" 
                                placeholder="Ingrese el valor sin puntos ni comas">
                        </div>
                        <small class="form-text text-muted">Ingrese el valor en pesos colombianos sin puntos ni comas</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveCosto()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
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

    <!-- Agregar este código justo antes del último </body> -->

    let costoModal;

    document.addEventListener('DOMContentLoaded', function() {
        costoModal = new bootstrap.Modal(document.getElementById('costoModal'));
    });

    function editCosto(surveyId, costoActual) {
        document.getElementById('editSurveyId').value = surveyId;
        document.getElementById('editCostoAprox').value = costoActual || '';
        costoModal.show();
    }

    function saveCosto() {
        const surveyId = document.getElementById('editSurveyId').value;
        const costoAprox = document.getElementById('editCostoAprox').value;

        if (!costoAprox || isNaN(costoAprox) || costoAprox < 0) {
            alert('Por favor ingrese un valor válido');
            return;
        }

        fetch('index.php?action=updateCosto', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `surveyId=${surveyId}&costoAprox=${costoAprox}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar el valor mostrado en la tabla sin recargar la página
                const row = document.querySelector(`tr[data-survey-id="${surveyId}"]`);
                if (row) {
                    const costoDisplay = row.querySelector('.costo-display');
                    if (costoDisplay) {
                        costoDisplay.textContent = '$ ' + Number(costoAprox).toLocaleString('es-CO');
                    }
                }
                costoModal.hide();
                window.location.reload(); // Asegurar que se actualice la vista
            } else {
                alert('Error al guardar el costo: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar el costo');
        });
    }
</script>
</body>
</html>