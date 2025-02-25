import { FormModule } from './modules/form.js';
import { UploadManager } from './modules/upload.js';
import { ProcessManager } from './modules/process.js';
import { FormValidator } from './modules/validation.js';

class App {
    constructor() {
        this.formModule = new FormModule();
        this.uploadManager = new UploadManager();
        this.processManager = new ProcessManager();
        this.validator = new FormValidator();
        this.errorMessageElement = document.getElementById('error-message');
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupEventListeners();
            this.hideError();
        });
    }

    setupEventListeners() {
        const form = document.getElementById('plantCountForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleSubmit(e);
            });
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        this.hideError();

        // Obtener referencias a elementos importantes
        const submitButton = e.target.querySelector('button[type="submit"]');
        const progressSection = document.querySelector('.progress-section');
        const processingStatus = document.getElementById('processingStatus');

        try {
            if (!this.validator.validateForm()) {
                return;
            }

            // Deshabilitar el botón
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span>Procesando...</span>';
            }

            // Mostrar progreso
            if (progressSection) {
                progressSection.style.display = 'block';
            }

            // Actualizar estado
            if (processingStatus) {
                processingStatus.textContent = 'Subiendo video...';
            }

            // Subir el archivo
            const uploadResult = await this.uploadManager.uploadFile();

            if (!uploadResult || !uploadResult.success) {
                throw new Error(uploadResult?.message || 'Error al subir el archivo');
            }

            // Actualizar estado
            if (processingStatus) {
                processingStatus.textContent = 'Procesando video...';
            }

            // Procesar el video
            const processResult = await this.processManager.processVideo();
            
            if (processResult && processResult.complete) {
                this.showResults(processResult);
            } else {
                throw new Error('El procesamiento no se completó correctamente');
            }

        } catch (error) {
            console.error('Error:', error);
            this.showError(error.message || 'Error al procesar el video');
            
            // Actualizar estado en caso de error
            if (processingStatus) {
                processingStatus.textContent = 'Error en el procesamiento';
            }
        } finally {
            // Restaurar el botón
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<span>Iniciar Procesamiento</span>';
            }
        }
    }

    showError(message) {
        if (this.errorMessageElement) {
            this.errorMessageElement.textContent = message;
            this.errorMessageElement.style.display = 'block';
        }
    }

    hideError() {
        if (this.errorMessageElement) {
            this.errorMessageElement.style.display = 'none';
        }
    }

    showResults(results) {
        const resultsContainer = document.getElementById('results');
        if (!resultsContainer) {
            console.error('No se encontró el contenedor de resultados');
            return;
        }

        // Actualizar el contador de plantas
        const plantCount = document.getElementById('plantCount');
        if (plantCount) {
            plantCount.textContent = results.total_unique_plants || '0';
        }

        // Actualizar estado del procesamiento
        const processingStatus = document.getElementById('processingStatus');
        if (processingStatus) {
            processingStatus.textContent = 'Procesamiento completado';
        }

        // Actualizar el enlace de descarga
        const downloadLink = document.getElementById('downloadVideo');
        if (downloadLink && results.processed_video_path) {
            downloadLink.href = results.processed_video_path;
            downloadLink.style.display = 'inline-block';
        }

        // Mostrar el contenedor de resultados
        resultsContainer.style.display = 'block';
    }
}

// Inicializar la aplicación
new App();