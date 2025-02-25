<form id="plantCountForm" class="form">
    <div class="form-group">
        <label class="form-label" for="block_number">Número de bloque:</label>
        <input type="number" id="block_number" name="block_number" class="form-control" required>
    </div>

    <div class="form-group">
        <label class="form-label" for="bed_number">Número de cama:</label>
        <input type="text" id="bed_number" name="bed_number" class="form-control" required pattern="[A-Za-z0-9]+">
    </div>

    <div class="form-group">
        <label class="form-label" for="year_week">Año y semana (YYWW):</label>
        <input type="text" id="year_week" name="year_week" class="form-control" required pattern="[0-9]{4}">
    </div>

    <div class="form-group">
        <label class="form-label" for="count_date">Fecha de conteo:</label>
        <input type="date" id="count_date" name="count_date" class="form-control" required>
    </div>

    <div class="form-group">
        <label class="form-label" for="video_file">Seleccionar Video:</label>
        <input type="file" id="video_file" name="video_file" class="form-control" accept=".mp4,.mov" required>
    </div>

    <button type="submit" class="btn btn-primary">Procesar</button>
</form>