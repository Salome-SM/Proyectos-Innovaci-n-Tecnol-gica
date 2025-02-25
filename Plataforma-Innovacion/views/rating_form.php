<?php
// rating_form.php

// Obtener los datos necesarios del controlador
$userEmail = $_SESSION['email'] ?? '';
$surveyId = $_GET['surveyId'] ?? null;
$survey = $survey ?? null; // El controlador debe pasar $survey
$calificadores = $calificadores ?? []; // El controlador debe pasar $calificadores
$userPermisos = $calificadores[$userEmail]['campos'] ?? [];
?>

<!-- rating_form.php -->
<form id="ratingForm">
    <input type="hidden" name="surveyId" value="<?php echo htmlspecialchars($_GET['surveyId'] ?? ''); ?>">
    <?php if (in_array('deseable', $userPermisos)): ?>
    <div class="mb-3">
        <label style="font-weight: bold;">¿Es deseable?</label>
        <select class="form-select" name="deseable" required>
            <option value="">Selecciona una opción</option>
            <?php for($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (in_array('impacta_estrategia', $userPermisos)): ?>
    <div class="mb-3">
        <label style="font-weight: bold;">¿Impacta la estrategia?</label>
        <select class="form-select" name="impactaEstrategia" required>
            <option value="">Selecciona una opción</option>
            <?php for($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (in_array('factible', $userPermisos)): ?>
    <div class="mb-3">
        <label style="font-weight: bold;">¿Es factible?</label>
        <select class="form-select" name="factible" required>
            <option value="">Selecciona una opción</option>
            <?php for($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if (in_array('viable', $userPermisos)): ?>
    <div class="mb-3">
        <label style="font-weight: bold;">¿Es viable?</label>
        <select class="form-select" name="viable" required>
            <option value="">Selecciona una opción</option>
            <?php for($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <?php endif; ?>
</form>