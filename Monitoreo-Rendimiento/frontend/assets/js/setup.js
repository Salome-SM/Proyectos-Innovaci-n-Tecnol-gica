$(document).ready(function () {
    let selectedType = '';
    let configuration = {
        type: '',
        selectedPersons: [],
        aster: [],
        pompon: []
    };

    function initializeSetup() {
        loadSelectedPersons();
        setupEventListeners();
    }

    function loadSelectedPersons() {
        $.ajax({
            url: '/L_Siembra/backend/php/api/class/list.php',
            method: 'GET',
            success: function (response) {
                if (response.status === 'success' && Array.isArray(response.data)) {
                    configuration.selectedPersons = response.data;
                    console.log('Personas cargadas:', configuration.selectedPersons);
                } else {
                    showError('Error cargando lista de personas');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error en la petición:', error);
                showError('Error de comunicación con el servidor');
            }
        });
    }

    function setupEventListeners() {
        $('.option-box').click(function() {
            const type = $(this).data('type');
            handleTypeSelection(type);
        });

        $('#startButton').click(handleStartButtonClick);
        setupDragAndDrop();
    }

    function handleTypeSelection(type) {
        console.log('Tipo seleccionado:', type);
        
        selectedType = type;
        configuration.type = type;
        
        $('.option-box').removeClass('selected');
        $(`.option-box[data-type="${type}"]`).addClass('selected');
        
        $('.mixed-selection').hide();
        
        if (type === 'mixed') {
            $('.mixed-selection').show();
            updateAvailablePersons();
        } else {
            // Para aster o pompon, asignar automáticamente todas las personas
            autoAssignPeople(type);
        }
        
        $('#startButton').prop('disabled', false);
    }

    function autoAssignPeople(type) {
        // Reiniciar arreglos
        configuration.aster = [];
        configuration.pompon = [];
        
        if (type === 'aster' || type === 'pompon') {
            // Establecer las personas seleccionadas
            configuration.selected_persons = configuration.selectedPersons;
        }
    }

    function updateAvailablePersons() {
        const container = $('.available-persons');
        container.empty();

        configuration.selectedPersons.forEach((person) => {
            if (!isPersonAssigned(person)) {
                const card = $(`
                    <div class="person-card" draggable="true" 
                         data-class="${person.class}" 
                         data-name="${person.name}">
                        ${person.name}
                    </div>
                `);
                container.append(card);
            }
        });

        // Actualizar contadores
        $('.aster-count').text(`${configuration.aster.length} personas`);
        $('.pompon-count').text(`${configuration.pompon.length} personas`);
    }

    function isPersonAssigned(person) {
        return configuration.aster.some(p => p.class === person.class) ||
               configuration.pompon.some(p => p.class === person.class);
    }

    function setupDragAndDrop() {
        $(document).on('dragstart', '.person-card', function(e) {
            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                class: $(this).data('class'),
                name: $(this).data('name')
            }));
            $(this).addClass('dragging');
        });

        $(document).on('dragend', '.person-card', function() {
            $(this).removeClass('dragging');
        });

        $('.category-dropzone').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $('.category-dropzone').on('dragleave', function() {
            $(this).removeClass('drag-over');
        });

        $('.category-dropzone').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
            const category = $(this).data('category');
            
            if (category === 'aster') {
                configuration.aster.push(data);
            } else {
                configuration.pompon.push(data);
            }
            
            updateAvailablePersons();
        });
    }

    function handleStartButtonClick() {
        const configToSave = {
            type: selectedType,
            aster: [],
            pompon: [],
            selected_persons: []
        };
    
        if (selectedType === 'mixed') {
            configToSave.aster = configuration.aster;
            configToSave.pompon = configuration.pompon;
        } else {
            configToSave.selected_persons = configuration.selectedPersons;
        }
    
        console.log('Enviando configuración:', configToSave);

        $.ajax({
            url: '/L_Siembra/backend/php/api/config/save.php',
            method: 'POST',
            data: { configuration: JSON.stringify(configToSave) },
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = '/L_Siembra/frontend/views/detector.html';
                } else {
                    showError(response.message || 'Error al guardar la configuración');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la petición:', error);
                console.error('Respuesta:', xhr.responseText);
                try {
                    const response = JSON.parse(xhr.responseText);
                    showError(response.message || 'Error de comunicación con el servidor');
                } catch (e) {
                    showError('Error de comunicación con el servidor');
                }
            }
        });
    }

    function showError(message) {
        $('#status-message')
            .text(message)
            .addClass('error');
            
        setTimeout(() => {
            $('#status-message')
                .text('Seleccione el tipo de detección')
                .removeClass('error');
        }, 3000);
    }

    // Inicializar
    initializeSetup();
});
