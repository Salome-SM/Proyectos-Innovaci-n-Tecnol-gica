<?php 
$pageTitle = 'Ver Ranking';
$currentPage = 'viewRanking';
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
    }

    .table {
        width: 100%;
        margin-top: 20px;
    }

    .table th {
        background-color: #025E73;
        color: white;
        text-align: center;
        padding: 12px;
    }

    .table td {
        vertical-align: middle;
        text-align: center;
        padding: 10px;
    }

    .points {
        font-weight: bold;
        padding: 5px 10px;
        border-radius: 15px;
    }

    .idea-points { background-color: #90EE90; }
    .participation-points { background-color: #FFB347; }
    .impact-points { background-color: #FFB6B9; }
    .total-points { background-color: #87CEEB; }

    .edit-btn {
        background-color: #025E73;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    .edit-btn:hover {
        background-color: #023E53;
    }

    .modal-header {
        background-color: #025E73;
        color: white;
    }

    .input-points {
        width: 80px;
        text-align: center;
    }

    .filters {
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .filter-group {
        flex: 1;
        max-width: 250px; /* Ajusta este valor según necesites */
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-left: 15px;
    }

    .filter-input {
        border: 1px solid #025E73;
        border-radius: 5px;
        padding: 8px;
        width: 100%;
    }
    .filter-container {
        display: flex;
        gap: 15px;
        align-items: center;
        max-width: 800px; /* Ajusta este valor según necesites */
        width: 100%;
    }

    .filter-btn {
        background-color: #EC6F17;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
    }

    .filter-btn:hover {
        background-color: #D15A0A;
    }

    .clear-filter-btn {
        background-color: #6c757d;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        margin-left: 10px;
    }

    .clear-filter-btn:hover {
        background-color: #5a6268;
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

        .table-container,
        .filter-container {
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
    <h2>Ranking</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="filters">
        <div class="filter-container">
            <div class="filter-group">
                <input type="text" id="nameFilter" class="filter-input" placeholder="Filtrar por nombre...">
            </div>
            <div class="filter-group">
                <input type="text" id="cedulaFilter" class="filter-input" placeholder="Filtrar por cédula...">
            </div>
            <div class="button-group">
                <button class="filter-btn" onclick="applyFilters()">Filtrar</button>
                <button class="clear-filter-btn" onclick="clearFilters()">Limpiar</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" id="rankingTable">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Ideas</th>
                    <th>Participación</th>
                    <th>Impacto</th>
                    <th>Total</th>
                    <th>Comentarios</th>
                    <?php if (isset($_SESSION['email']) && $_SESSION['email'] === 'innovaciontr@gmail.com'): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rankings as $index => $rank): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($rank['name']); ?></td>
                        <td><?php echo htmlspecialchars($rank['cedula']); ?></td>
                        <td><span class="points idea-points"><?php echo $rank['total_ideas']; ?></span></td>
                        <td><span class="points participation-points"><?php echo $rank['participation_points']; ?></span></td>
                        <td><span class="points impact-points"><?php echo $rank['impact_points']; ?></span></td>
                        <td><span class="points total-points"><?php echo $rank['total_points']; ?></span></td>
                        <td><?php echo htmlspecialchars($rank['comments'] ?? ''); ?></td>
                        <?php if (isset($_SESSION['email']) && $_SESSION['email'] === 'innovaciontr@gmail.com'): ?>
                            <td>
                                <button class="edit-btn" onclick="editPoints('<?php echo $rank['cedula']; ?>', <?php echo $rank['participation_points']; ?>, <?php echo $rank['impact_points']; ?>, '<?php echo addslashes($rank['comments'] ?? ''); ?>')">
                                    Editar
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para edición de puntos -->
<div class="modal fade" id="editPointsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Puntos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editCedula">
                <div class="mb-3">
                    <label class="form-label">Puntos de Participación</label>
                    <input type="number" id="editParticipationPoints" class="form-control input-points">
                </div>
                <div class="mb-3">
                    <label class="form-label">Puntos de Impacto</label>
                    <input type="number" id="editImpactPoints" class="form-control input-points">
                </div>
                <div class="mb-3">
                    <label class="form-label">Comentarios</label>
                    <textarea id="editComments" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="savePoints()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
let editModal;

document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editPointsModal'));

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

function editPoints(cedula, participationPoints, impactPoints, comments) {
    document.getElementById('editCedula').value = cedula;
    document.getElementById('editParticipationPoints').value = participationPoints;
    document.getElementById('editImpactPoints').value = impactPoints;
    document.getElementById('editComments').value = comments;
    editModal.show();
}

function savePoints() {
    const cedula = document.getElementById('editCedula').value;
    const participationPoints = document.getElementById('editParticipationPoints').value;
    const impactPoints = document.getElementById('editImpactPoints').value;
    const comments = document.getElementById('editComments').value;

    fetch('index.php?action=updatePoints', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cedula=${encodeURIComponent(cedula)}&participationPoints=${participationPoints}&impactPoints=${impactPoints}&comments=${encodeURIComponent(comments)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            editModal.hide();
            window.location.reload();
        } else {
            alert('Error al guardar los puntos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar los puntos');
    });
}

function applyFilters() {
    const nameFilter = document.getElementById('nameFilter').value.toLowerCase();
    const cedulaFilter = document.getElementById('cedulaFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#rankingTable tbody tr');
    let position = 1;

    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const cedula = row.cells[2].textContent.toLowerCase();
        
        if (name.includes(nameFilter) && cedula.includes(cedulaFilter)) {
            row.style.display = '';
            row.cells[0].textContent = position++;
        } else {
            row.style.display = 'none';
        }
    });
}

function clearFilters() {
    document.getElementById('nameFilter').value = '';
    document.getElementById('cedulaFilter').value = '';
    const rows = document.querySelectorAll('#rankingTable tbody tr');
    let position = 1;
    
    rows.forEach(row => {
        row.style.display = '';
        row.cells[0].textContent = position++;
    });
}
</script>

</body>
</html>