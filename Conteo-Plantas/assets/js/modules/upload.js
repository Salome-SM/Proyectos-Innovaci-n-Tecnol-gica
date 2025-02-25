// Archivo: assets/js/modules/upload.js
import { apiRequest } from '../utils/api.js';

export class UploadManager {
    constructor() {
        this.chunkSize = 1024 * 1024; // 1MB
        this.progressBar = document.getElementById('uploadProgressBar');
        this.progressText = document.getElementById('uploadProgress');
    }

    async uploadFile() {
        try {
            const fileInput = document.getElementById('video_file');
            const file = fileInput?.files[0];
            
            if (!file) {
                throw new Error('No se ha seleccionado ningún archivo');
            }

            // Verificar extensión del archivo
            const fileName = file.name;
            const extension = fileName.split('.').pop().toLowerCase();
            if (!['mov', 'mp4'].includes(extension)) {
                throw new Error(`Tipo de archivo no permitido. Solo se permiten archivos MOV y MP4.`);
            }

            const chunks = Math.ceil(file.size / this.chunkSize);
            let uploadedChunks = 0;

            for (let i = 0; i < chunks; i++) {
                const chunk = this.getChunk(file, i);
                const formData = new FormData();
                
                // Agregar información del archivo al FormData
                formData.append('file', chunk);
                formData.append('chunk', i);
                formData.append('chunks', chunks);
                formData.append('name', file.name); // Agregar nombre original del archivo

                await this.uploadChunk(formData);
                uploadedChunks++;
                this.updateProgress((uploadedChunks / chunks) * 100);
            }

            return { success: true, fileName: file.name };
        } catch (error) {
            console.error('Upload error:', error);
            throw error;
        }
    }

    getChunk(file, index) {
        const start = index * this.chunkSize;
        const end = Math.min(file.size, start + this.chunkSize);
        return file.slice(start, end);
    }

    async uploadChunk(formData) {
        const response = await fetch('/plant_counter/upload.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no válida del servidor');
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Error al subir el chunk');
        }
        
        return data;
    }

    updateProgress(percentage) {
        if (this.progressBar) {
            this.progressBar.style.width = `${percentage}%`;
        }
        if (this.progressText) {
            this.progressText.textContent = `${Math.round(percentage)}%`;
        }
    }
}