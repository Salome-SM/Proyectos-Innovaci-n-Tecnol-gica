export class ProcessManager {
    constructor() {
        this.progressBar = document.getElementById('processProgressBar');
        this.progressText = document.getElementById('processProgress');
        this.resultsContainer = document.getElementById('results');
        this.baseUrl = window.location.pathname.includes('plant_counter') 
            ? '/plant_counter' 
            : '';
        this.errorContainer = document.getElementById('error-message');
        // Aumentamos el timeout a 30 minutos (1800000 ms)
        this.timeout = 1800000;
    }

    async processVideo() {
        try {
            const params = this.getFormParameters();
            const url = `${this.baseUrl}/process.php?${new URLSearchParams(params)}`;
            console.log('Intentando conectar a:', url);

            return new Promise((resolve, reject) => {
                let eventSource;
                try {
                    eventSource = new EventSource(url);
                    console.log('EventSource creado');
                } catch (e) {
                    console.error('Error al crear EventSource:', e);
                    reject(e);
                    return;
                }

                eventSource.onopen = (event) => {
                    console.log('Conexión SSE establecida:', event);
                };

                eventSource.onmessage = (event) => {
                    console.log('Mensaje SSE recibido:', event.data);
                    try {
                        const data = JSON.parse(event.data);
                        
                        // Manejar errores
                        if (data.error) {
                            console.error('Error recibido del servidor:', data.error);
                            this.showError(data.error);
                            eventSource.close();
                            reject(new Error(data.error));
                            return;
                        }

                        // Manejar mensajes
                        if (data.message) {
                            console.log('Mensaje:', data.message);
                        }

                        // Manejar progreso
                        if (data.progress !== undefined) {
                            this.updateProgress(data.progress);
                        }

                        // Manejar salida del proceso Python
                        if (data.output) {
                            try {
                                const pythonData = JSON.parse(data.output);
                                if (pythonData.progress !== undefined) {
                                    this.updateProgress(pythonData.progress);
                                }
                                if (pythonData.total_unique_plants !== undefined) {
                                    this.updatePlantCount(pythonData.total_unique_plants);
                                }
                            } catch (e) {
                                console.log('Mensaje no JSON de Python:', data.output);
                            }
                        }
                        
                        // Manejar completado
                        if (data.complete) {
                            console.log('Procesamiento completado:', data);
                            eventSource.close();
                            this.showResults(data);
                            resolve(data);
                        }
                    } catch (error) {
                        console.error('Error al procesar mensaje:', error, 'Datos recibidos:', event.data);
                        console.warn('Mensaje no JSON recibido:', event.data);
                    }
                };

                eventSource.onerror = (error) => {
                    console.error('Error detallado en EventSource:', {
                        readyState: eventSource.readyState,
                        error: error,
                        url: url
                    });

                    if (eventSource.readyState === EventSource.CLOSED) {
                        console.log('Conexión cerrada por el servidor');
                    } else {
                        console.log('Error en la conexión, cerrando EventSource');
                        eventSource.close();
                        reject(new Error('Falló la conexión de procesamiento. Verifica la consola para más detalles.'));
                    }
                };

                // Agregar timeout de seguridad extendido (30 minutos)
                setTimeout(() => {
                    if (eventSource && eventSource.readyState !== EventSource.CLOSED) {
                        console.log('Cerrando conexión por timeout después de 30 minutos');
                        eventSource.close();
                        reject(new Error('Timeout de procesamiento después de 30 minutos'));
                    }
                }, this.timeout);
            });
        } catch (error) {
            console.error('Error en processVideo:', error);
            throw error;
        }
    }

    getFormParameters() {
        const params = {
            block_number: document.getElementById('block_number').value,
            bed_number: document.getElementById('bed_number').value,
            year_week: document.getElementById('year_week').value,
            count_date: document.getElementById('count_date').value,
            variety: document.getElementById('variety').value
        };
    
        const videoFile = document.getElementById('video_file').files[0];
        if (videoFile) {
            params.video_file = videoFile.name;
        }
    
        console.log('Parámetros del formulario:', params);
        return params;
    }

    updateProgress(percentage) {
        if (this.progressBar) {
            this.progressBar.style.width = `${percentage}%`;
        }
        if (this.progressText) {
            this.progressText.textContent = `${Math.round(percentage)}%`;
        }
    }

    updatePlantCount(count) {
        const plantCount = document.getElementById('plantCount');
        if (plantCount) {
            plantCount.textContent = count;
        }
    }

    showError(message) {
        if (this.errorContainer) {
            this.errorContainer.textContent = message;
            this.errorContainer.style.display = 'block';
        }
    }

    hideError() {
        if (this.errorContainer) {
            this.errorContainer.style.display = 'none';
        }
    }

    showResults(data) {
        if (this.resultsContainer) {
            const plantCount = document.getElementById('plantCount');
            if (plantCount) {
                plantCount.textContent = data.total_unique_plants || '0';
            }
            this.resultsContainer.style.display = 'block';

            // Actualizar el enlace de descarga
            if (data.processed_video_path) {
                const downloadLink = document.getElementById('downloadVideo');
                if (downloadLink) {
                    downloadLink.href = `${this.baseUrl}/${data.processed_video_path}`;
                    downloadLink.style.display = 'inline-block';
                    downloadLink.onclick = (e) => {
                        e.preventDefault();
                        window.open(`${this.baseUrl}/${data.processed_video_path}`, '_blank');
                    };
                }
            }
        }
    }
}