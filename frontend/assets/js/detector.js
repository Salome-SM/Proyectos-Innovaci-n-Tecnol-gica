let configuration;
const baseUrl = window.location.origin + '/L_Siembra';
 // Constantes para las rutas de API
 const API = {
    config: baseUrl + '/backend/php/api/config/check.php',
    detection: {
        start: `${baseUrl}/backend/php/api/detection/start.php`,
        stop: `${baseUrl}/backend/php/api/detection/stop.php`,
        status: `${baseUrl}/backend/php/api/detection/status.php`,
        togglePause: `${baseUrl}/backend/php/api/detection/toggle_pause.php`,
        forceStop: `${baseUrl}/backend/php/api/detection/force_stop.php`,
        downloadExcel: `${baseUrl}/backend/php/api/detection/download.php`
    }
};

function checkConfiguration() {
    return new Promise((resolve, reject) => {
        console.log('Verificando configuración en:', API.config);
        
        $.ajax({
            url: API.config,
            method: 'GET',
            success: function(response) {
                console.log('Respuesta completa de verificación:', response);
                if (response && response.status === 'success') {
                    resolve(response.data);
                } else {
                    console.log('Configuración no válida:', response);
                    reject(new Error('Configuración no válida'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en verificación:', xhr.responseText);
                reject(new Error(error));
            }
        });
    });
}

$(document).ready(function() {
    console.log('Iniciando aplicación de detección...');
    // Constantes y variables de estado
    let detectionActive = false;
    let isPaused = false;
    let updateInterval;
    const baseUrl = '/L_Siembra';

    console.log('URL actual:', window.location.href);
    console.log('Origin:', window.location.origin);
    console.log('Pathname:', window.location.pathname);
    console.log('BaseURL:', baseUrl);

    // Funciones de inicialización
    function initializeApp() {
        if (window.location.pathname.includes('setup.html')) {
            console.log('En página de setup, omitiendo verificación');
            return;
        }

        console.log('Iniciando aplicación...');
        
        checkConfiguration()
            .then((config) => {
                configuration = config;
                console.log('Configuración válida:', configuration);
                initializeInterface();
            })
            .catch(error => {
                console.error('Error en configuración:', error);
                window.location.href = baseUrl + '/frontend/views/setup.html';
            });
    }

    function initializeInterface() {
        console.log('Inicializando interfaz...');
        updateButtonStates(false, false);
        setupEventListeners();
        updateStatus('✅ Sistema listo', '#e8f5e9');
    }

    function setupEventListeners() {
        console.log('Configurando event listeners...');
        
        $('#startDetection').off('click').on('click', function() {
            console.log('Click en Start Detection');
            handleDetectionStart();
        });

        $('#pauseDetection').off('click').on('click', function() {
            console.log('Click en Pause Detection');
            handleDetectionPause();
        });

        $('#resumeDetection').off('click').on('click', function() {
            console.log('Click en Resume Detection');
            handleDetectionResume();
        });

        $('#stopDetection').off('click').on('click', function() {
            console.log('Click en Stop Detection');
            handleDetectionStop();
        });

        $('#forceStop').off('click').on('click', function() {
            console.log('Click en Force Stop');
            handleForceStop();
        });

        $('#downloadExcel').off('click').on('click', function() {
            console.log('Click en Download Excel');
            handleExcelDownload();
        });
    }

    // Manejadores de eventos de detección
    function handleDetectionStart() {
        if (!configuration) {
            console.error('Configuración no definida');
            alert('Configuración no disponible');
            return;
        }
    
        $('#startDetection').prop('disabled', true);
        updateStatus('⏳ Iniciando detección...', '#fff3e0');
    
        $.ajax({
            url: API.detection.start,
            method: 'POST',
            data: 'configuration=' + encodeURIComponent(JSON.stringify(configuration)),
            contentType: 'application/x-www-form-urlencoded',
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.status === 'success') {
                    detectionActive = true;
                    isPaused = false;
                    updateStatus('✅ Detección iniciada', '#e8f5e9');
                    updateButtonStates(true, false);
                    startTableUpdates();
                } else {
                    handleStartError(response);
                }
            },
            error: handleAjaxError
        });
    }

    function handleDetectionPause() {
        $('#pauseDetection').prop('disabled', true);
        updateStatus('⏸️ Pausando detección...', '#fff3e0');
    
        $.ajax({
            url: API.detection.togglePause,
            method: 'POST',
            data: { action: 'pause' },
            success: function(response) {
                console.log('Respuesta de pause:', response);
                if (response.status === 'success') {
                    isPaused = true;
                    updateStatus('⏸️ Detección pausada', '#e8f5e9');
                    updateButtonStates(true, true);
                    $('.data-table').addClass('paused');
                    stopTableUpdates();
                    
                    // Mantener la última actualización visible
                    const currentTime = $('#lastUpdate').text();
                    $('#lastUpdate').text(`Sistema en pausa desde ${currentTime.split(': ')[1]}`);
                } else {
                    updateStatus('❌ Error al pausar', '#ffebee');
                    $('#pauseDetection').prop('disabled', false);
                }
            },
            error: handleAjaxError
        });
    }
    
    function handleDetectionResume() {
        $('#resumeDetection').prop('disabled', true);
        updateStatus('▶️ Reanudando detección...', '#fff3e0');
    
        $.ajax({
            url: API.detection.togglePause,
            method: 'POST',
            data: { action: 'resume' },
            success: function(response) {
                console.log('Respuesta de resume:', response);
                if (response.status === 'success') {
                    isPaused = false;
                    updateStatus('▶️ Detección reanudada', '#e8f5e9');
                    updateButtonStates(true, false);
                    $('.data-table').removeClass('paused');
                    startTableUpdates();
                } else {
                    updateStatus('❌ Error al reanudar', '#ffebee');
                    $('#resumeDetection').prop('disabled', false);
                }
            },
            error: handleAjaxError
        });
    }

    function handleDetectionStop() {
        $('#stopDetection').prop('disabled', true);
        updateStatus('⏳ Deteniendo detección...', '#fff3e0');
    
        $.ajax({
            url: API.detection.stop,
            method: 'POST',
            timeout: 30000,
            success: function(response) {
                console.log('Respuesta de stop:', response);
                if (response.status === 'success') {
                    detectionActive = false;
                    isPaused = false;
                    updateStatus('✅ Detección detenida correctamente', '#e8f5e9');
                    updateButtonStates(false, false);
                    stopTableUpdates();
                    hideEmergencyStop();
                    
                    // Llamar a la función existente de descarga de Excel
                    handleExcelDownload();
                } else {
                    updateStatus('❌ Error al detener. Use detención forzada.', '#ffebee');
                    showEmergencyStop();
                    $('#stopDetection').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                handleAjaxError(xhr, status, error);
                showEmergencyStop();
                $('#stopDetection').prop('disabled', false);
            }
        });
    }

    function handleForceStop() {
        $.ajax({
            url: API.detection.forceStop,
            method: 'POST',
            timeout: 30000,
            success: function(response) {
                console.log('Respuesta de force stop:', response);
                updateStatus('✅ Detección detenida forzadamente', '#e8f5e9');
                updateButtonStates(false, false);
                hideEmergencyStop();
                detectionActive = false;
                stopTableUpdates();
                
                setTimeout(() => {
                    location.reload();
                }, 2000);
            },
            error: handleAjaxError
        });
    }

    function handleExcelDownload() {
        $('#downloadExcel').prop('disabled', true);
        updateStatus('📊 Preparando archivo Excel...', '#e3f2fd');
    
        // Primero verificar si el archivo existe
        $.ajax({
            url: API.detection.downloadExcel,
            method: 'HEAD',
            success: function() {
                // Si el archivo existe, iniciamos la descarga
                const downloadFrame = $('<iframe>', {
                    src: API.detection.downloadExcel,
                    style: 'display: none'
                }).appendTo('body');
    
                setTimeout(() => {
                    downloadFrame.remove();
                    $('#downloadExcel').prop('disabled', false);
                    if (detectionActive) {
                        updateStatus('✅ Detección activa', '#e8f5e9');
                    } else {
                        updateStatus('Sistema listo', '#f5f5f5');
                    }
                }, 3000);
            },
            error: function(xhr) {
                $('#downloadExcel').prop('disabled', false);
                try {
                    const response = JSON.parse(xhr.responseText);
                    updateStatus('❌ ' + (response.message || 'Error al descargar Excel'), '#ffebee');
                } catch {
                    updateStatus('❌ Error al descargar Excel', '#ffebee');
                }
            }
        });
    }

    // Funciones de UI
    function showEmergencyStop() {
        $('#emergencyStop').show();
        $('#stopDetection').prop('disabled', true);
    }

    function hideEmergencyStop() {
        $('#emergencyStop').hide();
        $('#stopDetection').prop('disabled', false);
    }

    function updateButtonStates(isRunning, isPaused) {
        console.log('Actualizando estados de botones:', {isRunning, isPaused});
        $('#startDetection').prop('disabled', isRunning);
        $('#pauseDetection').prop('disabled', !isRunning || isPaused);
        $('#resumeDetection').prop('disabled', !isRunning || !isPaused);
        $('#stopDetection').prop('disabled', !isRunning);
        $('#downloadExcel').prop('disabled', false);
    }

    function updateStatus(message, backgroundColor) {
        $('#status').text(message).css('background-color', backgroundColor);
    }

    // Funciones de tabla
    function startTableUpdates() {
        console.log('Iniciando actualizaciones de tabla');
        if (window.tableUpdateInterval) {
            clearInterval(window.tableUpdateInterval);
        }
        updateDetectionTable(); // Actualización inicial
        window.tableUpdateInterval = setInterval(updateDetectionTable, 2000);
        console.log('Intervalo de actualización establecido');
    }

    function stopTableUpdates() {
        if (window.tableUpdateInterval) {
            clearInterval(window.tableUpdateInterval);
            window.tableUpdateInterval = null;
        }
    }

    function updateDetectionTable() {
        if (!detectionActive) return;
        
        console.log('Solicitando actualización de estado...');
        
        $.ajax({
            url: API.detection.status,
            method: 'GET',
            success: function(response) {
                console.log('Respuesta de estado completa:', response);
                
                // Aceptar tanto 'success' como 'active' como estados válidos
                if (response.status === 'success' || response.status === 'active') {
                    if (response.data && Array.isArray(response.data)) {
                        // Filtrar solo registros con conteos mayores a 0
                        const activeData = response.data.filter(item => item.current_count > 0);
                        console.log('Datos con conteos > 0:', activeData);
                        
                        if (activeData.length > 0) {
                            console.log('Actualizando tabla con datos activos');
                            updateTableContent(response.data);
                            $('#lastUpdate').text(`Última actualización: ${new Date().toLocaleTimeString()}`);
                        } else {
                            console.log('No hay registros con conteos > 0');
                            $('#detectionData').html('<tr><td colspan="5" class="text-center">Sin detecciones registradas</td></tr>');
                        }
                    } else {
                        console.log('Sin datos de detección');
                        $('#detectionData').html('<tr><td colspan="5" class="text-center">Sin datos de detección</td></tr>');
                    }
                } else {
                    console.warn('Estado de respuesta inesperado:', response.status);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error actualizando tabla:', {
                    xhr: xhr,
                    status: status,
                    error: error
                });
            }
        });
    }
    
    function updateTableContent(data) {
        console.log('Actualizando contenido de tabla con datos:', data);
        const tbody = $('#detectionData');
        tbody.empty();
    
        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center">Sin datos de detección</td></tr>');
            return;
        }
    
        // Ordenar datos por conteo (mayor a menor)
        data.sort((a, b) => b.current_count - a.current_count);
    
        data.forEach(person => {
            console.log('Procesando persona:', person.name, 'con conteo:', person.current_count);
            const row = createTableRow(person);
            tbody.append(row);
        });
    }

    function createTableRow(person) {
        const progressPercentage = formatProgress(person.current_count, person.target, person.deficit);
        const statusClass = getStatusClass(person.current_count, person.target, person.deficit);
        
        return `
            <tr>
                <td>${person.name}</td>
                <td>${person.current_count}</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar-container">
                            <div class="progress-bar ${statusClass}" 
                                 style="width: ${Math.min(100, progressPercentage)}%">
                            </div>
                        </div>
                        <span class="progress-text">${Math.round(progressPercentage)}%</span>
                    </div>
                </td>
                <td>
                    <span class="status-indicator ${statusClass}">
                        ${getStatusText(person)}
                    </span>
                </td>
                <td>
                    <div class="target-info">
                        <span class="target-number">Meta: ${person.target}/hora</span>
                        ${person.deficit > 0 ? 
                            `<span class="deficit-warning">Recuperar: ${person.deficit}</span>` : 
                            '<span class="target-ok">✓ ¡Vas bien!</span>'}
                    </div>
                </td>
            </tr>
        `;
    }

    function formatProgress(current, target, deficit) {
        const totalNeeded = target + deficit;
        const percentage = (current / totalNeeded) * 100;
        return Math.min(100, percentage);
    }

    function getStatusClass(current, target, deficit) {
        if (deficit > 0) {
            return current >= (target + deficit) ? 'status-ok' : 'status-deficit';
        }
        return current >= target ? 'status-ok' : 'status-warning';
    }

    function getStatusText(person) {
        if (person.deficit > 0) {
            return `Déficit: ${person.deficit}`;
        }
        if (person.current_count >= person.target) {
            return '¡Excelente!';
        }
        return `Faltan: ${person.target - person.current_count}`;
    }

    // Manejadores de error
    function handleStartError(response) {
        let errorMsg = 'Error: ' + response.message;
        if (response.details && response.details.log_content) {
            console.error('Detalles del error:', response.details);
            errorMsg += '\n\nLog de Python:\n' + response.details.log_content;
        }
        updateStatus('❌ ' + errorMsg.replace(/\n/g, '<br>'), '#ffebee');
        updateButtonStates(false, false);
        $('#startDetection').prop('disabled', false);
    }

    function handleAjaxError(xhr, status, error) {
        console.error('Error AJAX:', {xhr, status, error});
        let errorMsg = 'Error de comunicación con el servidor';
        try {
            const response = JSON.parse(xhr.responseText);
            errorMsg = response.message || error;
        } catch (e) {
            errorMsg = xhr.responseText || error;
        }
        
        updateStatus('❌ ' + errorMsg, '#ffebee');
        updateButtonStates(false, false);
    }

    // Iniciar la aplicación
    initializeApp();
});