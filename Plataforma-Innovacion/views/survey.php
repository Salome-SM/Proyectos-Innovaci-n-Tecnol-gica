<?php 
$pageTitle = 'Hacer Encuesta';
$currentPage = 'survey';
include 'header.php';
?>

<style>
    .container {
        background-color: #ffffff;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-top: 80px;
        max-width: 1200px;
        margin-right: auto;
        transition: all 0.3s ease;
    }
    h2 {
        color: #025E73;
        text-align: center;
        margin-bottom: 30px;
        font-weight: bold;
    }
    .form-control, .form-select {
        border-color: #025E73;
    }
    .form-control:focus, .form-select:focus {
        border-color: #FFC000;
        box-shadow: 0 0 0 0.2rem rgba(255, 192, 0, 0.25);
    }
    .btn-success {
        background-color:#EC6F17;
        border-color: #EC6F17;
        color: #ffffff;
    }
    .btn-success:hover {
        background-color:  #025E73;
        border-color:  #025E73;
    }
    .hidden {
        display: none;
    }
    label {
        color: #025E73;
        font-weight: bold;
    }
    .description {
        font-size: 0.9em;
        color: #666;
        margin-top: 5px;
    }
    .sidebar .nav-link.active {
        background-color: #025E73;
        color: white;
    }
    .sidebar-expanded .container {
        margin-left: 280px;
        width: calc(100% - 310px);
    }
    .sidebar-collapsed .container {
        margin-left: 90px;
        width: calc(100% - 120px);
    }
    
    @media (max-width: 768px) {
        .sidebar-expanded .container,
        .sidebar-collapsed .container {
            margin-left: 0;
            width: 100%;
        }
    }
    
</style>

<div class="container mt-5">
    <h2>Encuesta</h2>
    <?php 
    if (isset($message)) echo "<div class='alert alert-success'>$message</div>";
    if (isset($error)) echo "<div class='alert alert-danger'>$error</div>";
    ?>
    <form method="POST" action="index.php?action=saveSurvey" enctype="multipart/form-data">
        <!-- Campos comunes -->
        <div class="mb-3">
            <label for="name" class="form-label">Nombre</label>
            <input type="text" name="name" id="name" class="form-control" required placeholder="Ingrese su nombre completo">
        </div>
        <div class="mb-3">
            <label for="cedula" class="form-label">Cedula</label>
            <input type="text" name="cedula" id="cedula" class="form-control" required placeholder="Ingrese su número de cédula sin puntos ni comas">
        </div>
        <div class="mb-3">
            <label for="sede" class="form-label">Sede</label>
            <select name="sede" id="sede" class="form-select" required>
                <option value="">Seleccione una sede</option>
                <option value="AC">AC</option>
                <option value="FE">FE</option>
                <option value="MT">MT</option>
                <option value="OL">OL</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="area" class="form-label">Area a la que pertenece</label>
            <select name="area" id="area" class="form-select" required>
                <option value="" disabled selected>Seleccione el área a la que pertenece</option>
                <option value="MIPE">MIPE</option>
                <option value="MIRFE">MIRFE</option>
                <option value="Labores Culturales">Labores Culturales</option>
                <option value="Exvitro">Exvitro</option>
                <option value="Siembra">Siembra</option>
                <option value="Calidad">Calidad</option>
                <option value="Mantenimiento">Mantenimiento</option>
                <option value="Propagación">Propagación</option>
                <option value="Corte">Corte</option>
                <option value="Poscosecha">Poscosecha</option>
                <option value="Gestión Humana">Gestión Humana</option>
                <option value="Innovación">Innovación</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="initiativeType" class="form-label">Tipo de iniciativa</label>
            <select name="initiativeType" id="initiativeType" class="form-select" required>
                <option value="">Seleccione el tipo de iniciativa</option>
                <option value="idea" data-description="Una propuesta innovadora para mejorar procesos o crear nuevos productos/servicios.">Idea</option>
                <option value="problema" data-description="Un desafío o dificultad identificada que requiere una solución.">Problema</option>
                <option value="reto" data-description="Un objetivo ambicioso que requiere esfuerzo y creatividad para ser alcanzado.">Reto</option>
            </select>
            <div id="initiativeTypeDescription" class="description"></div>
        </div>

        <!-- Sección común para todas las iniciativas -->
        <div id="commonSection" class="hidden">
            <div class="mb-3">
                <label for="oportunidad" class="form-label">¿Dónde viste la oportunidad?</label>
                <select name="oportunidad" id="oportunidad" class="form-select">
                    <option value="">Seleccione una opción</option>
                    <option value="MIPE">MIPE</option>
                    <option value="MIRFE">MIRFE</option>
                    <option value="Labores Culturales">Labores Culturales</option>
                    <option value="Exvitro">Exvitro</option>
                    <option value="Siembra">Siembra</option>
                    <option value="Calidad">Calidad</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="Propagación">Propagación</option>
                    <option value="Corte">Corte</option>
                    <option value="Poscosecha">Poscosecha</option>
                    <option value="Gestión Humana">Gestión Humana</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="impacto" class="form-label">¿Qué impactas con tu iniciativa?</label>
                <select name="impacto" id="impacto" class="form-select">
                    <option value="">Seleccione una opción</option>
                    <option value="seguridad_salud" data-description="Mejora las condiciones de trabajo para reducir riesgos y promover el bienestar de los empleados.">Seguridad y salud en el trabajo</option>
                    <option value="confort" data-description="Aumenta la comodidad y satisfacción de los empleados en su entorno laboral.">Confort</option>
                    <option value="productividad" data-description="Incrementa la eficiencia y el rendimiento en los procesos de trabajo.">Productividad</option>
                    <option value="innovacion" data-description="Introduce nuevas ideas, métodos o tecnologías para mejorar procesos o crear nuevos productos/servicios.">Innovación</option>
                    <option value="fidelización" data-description="Efecto que tiene una acción en la lealtad de los empleados hacia la empresa.">Fidelización</option>
                </select>
                <div id="impactoDescription" class="description"></div>
            </div>
        </div>

        <!-- Sección para Idea -->
        <div id="ideaSection" class="hidden">
            <div class="mb-3">
                <label for="nombreIdea" class="form-label">Nombre de la idea</label>
                <input type="text" name="nombreIdea" id="nombreIdea" class="form-control" placeholder="Ingrese el nombre de su idea">
            </div>
            <div class="mb-3">
                <label for="descripcionIdea" class="form-label">Describe tu idea</label>
                <textarea name="descripcionIdea" id="descripcionIdea" class="form-control" rows="3" placeholder="Describa su idea en detalle"></textarea>
            </div>
        </div>

        <!-- Sección para Problema -->
        <div id="problemaSection" class="hidden">
            <div class="mb-3">
                <label for="nombreProblema" class="form-label">Nombre del problema</label>
                <input type="text" name="nombreProblema" id="nombreProblema" class="form-control" placeholder="Ingrese el nombre del problema">
            </div>
            <div class="mb-3">
                <label for="descripcionProblema" class="form-label">Describe el problema</label>
                <textarea name="descripcionProblema" id="descripcionProblema" class="form-control" rows="3" placeholder="Describa el problema en detalle"></textarea>
            </div>
        </div>

        <!-- Sección para Reto -->
        <div id="retoSection" class="hidden">
            <div class="mb-3">
                <label for="nombreReto" class="form-label">Nombre del reto</label>
                <input type="text" name="nombreReto" id="nombreReto" class="form-control" placeholder="Ingrese el nombre del reto">
            </div>
            <div class="mb-3">
                <label for="descripcionReto" class="form-label">Describe el reto</label>
                <textarea name="descripcionReto" id="descripcionReto" class="form-control" rows="3" placeholder="Describa el reto en detalle"></textarea>
            </div>
        </div>

        <!-- Sección común para archivos adjuntos -->
        <div class="mb-3">
            <label for="attachment" class="form-label">Adjuntar archivo (foto, video, bloc de notas, excel)</label>
            <input type="file" name="attachment" id="attachment" class="form-control">
        </div>
        <input type="hidden" name="fechaIngreso" id="fechaIngreso" value="<?php echo date('Y-m-d H:i:s'); ?>">
        <div class="d-grid">
            <button type="submit" class="btn btn-success">Enviar</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const container = document.querySelector('.container');
    const body = document.body;
    var initiativeType = document.getElementById('initiativeType');
    var commonSection = document.getElementById('commonSection');
    var ideaSection = document.getElementById('ideaSection');
    var problemaSection = document.getElementById('problemaSection');
    var retoSection = document.getElementById('retoSection');
    var initiativeTypeDescription = document.getElementById('initiativeTypeDescription');
    var impacto = document.getElementById('impacto');
    var impactoDescription = document.getElementById('impactoDescription');

    function updateLayout() {
        if (sidebar && sidebar.classList.contains('expanded')) {
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
    if (sidebar) {
        const observer = new MutationObserver(updateLayout);
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }

    initiativeType.addEventListener('change', function() {
        commonSection.classList.add('hidden');
        ideaSection.classList.add('hidden');
        problemaSection.classList.add('hidden');
        retoSection.classList.add('hidden');

        if (this.value) {
            commonSection.classList.remove('hidden');
        }

        if (this.value === 'idea') {
            ideaSection.classList.remove('hidden');
        } else if (this.value === 'problema') {
            problemaSection.classList.remove('hidden');
        } else if (this.value === 'reto') {
            retoSection.classList.remove('hidden');
        }

        initiativeTypeDescription.textContent = this.options[this.selectedIndex].dataset.description || '';
    });

    impacto.addEventListener('change', function() {
        impactoDescription.textContent = this.options[this.selectedIndex].dataset.description || '';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>