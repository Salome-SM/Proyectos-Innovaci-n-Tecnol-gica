// Archivo: assets/js/modules/form.js
export class FormModule {
    constructor() {
        this.form = document.getElementById('plantCountForm');
        this.initForm();
    }

    initForm() {
        this.form.innerHTML = `
            <div class="form-header">
                <h2>Datos del Conteo</h2>
            </div>
            <div class="form-section">
                <div class="form-group">
                    <label class="form-label" for="block_number">Número de bloque:</label>
                    <input type="number" id="block_number" name="block_number" class="form-control" required 
                           placeholder="Ingrese el número de bloque">
                </div>

                <div class="form-group">
                    <label class="form-label" for="bed_number">Número de cama:</label>
                    <input type="text" id="bed_number" name="bed_number" class="form-control" required 
                           pattern="[A-Za-z0-9]+" placeholder="Ej: A1, B2, C3">
                </div>

                <div class="form-group">
                    <label class="form-label" for="variety">Variedad:</label>
                    <select id="variety" name="variety" class="form-control" required>
                        <option value="">Seleccionar variedad</option>
                        <option value="white">White</option>
                        <option value="rosse">Rosse</option>
                        <option value="yellow">Yellow</option>
                        <option value="crimson">Crimson</option>
                        <option value="orleans_white">Orleans White</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="year_week">Año y semana (YYWW):</label>
                    <input type="text" id="year_week" name="year_week" class="form-control" required 
                           pattern="[0-9]{4}" placeholder="Ej: 2324 para semana 24 del 2023">
                </div>

                <div class="form-group">
                    <label class="form-label" for="count_date">Fecha de conteo:</label>
                    <input type="date" id="count_date" name="count_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="video_file">Seleccionar Video:</label>
                    <input type="file" id="video_file" name="video_file" class="form-control" 
                           accept=".mp4,.mov" required>
                </div>
            </div>
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <span>Procesar Video</span>
                </button>
            </div>
        `;
    }
}