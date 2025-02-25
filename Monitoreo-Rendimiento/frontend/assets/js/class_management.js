const ClassManagement = {
    // Variables globales
    availableNames: [],
    assignments: {},
    usedNames: new Set(),
    PREDEFINED_CLASSES: [
        'key', 'micro', 'next', 'qr', 'magnet', 'puzzle', 'umbrella', 
        'women', 'flower', 'x', 'serious', 'sound', 'heart', 'star',
        'cloud', 'pawn', 'eye', 'diana', 'ray', 'rocket', 'light',
        'cat', 'gear', 'locker'
    ],
    // Diccionario de traducciones
    CLASS_TRANSLATIONS: {
        key: "Llave",
        micro: "Micrófono",
        next: "Siguiente",
        qr: "Código QR",
        magnet: "Imán",
        puzzle: "Rompecabezas",
        umbrella: "Sombrilla",
        women: "Mujer",
        flower: "Flor",
        x: "x",
        serious: "Serio",
        sound: "Sonido",
        heart: "Corazón",
        star: "Estrella",
        cloud: "Nube",
        pawn: "Peón",
        eye: "Ojo",
        diana: "Diana",
        ray: "Rayo",
        rocket: "Cohete",
        light: "Luz",
        cat: "Gato",
        gear: "Engranaje",
        locker: "Candado"
    },

    init: async function() {
        try {
            await this.loadNamesFromFile();
            await this.loadExistingAssignments();
            this.renderUI();
            this.setupEventListeners();
        } catch (error) {
            this.showError('Error inicializando: ' + error.message);
        }
    },

    loadNamesFromFile: async function() {
        try {
            const response = await $.ajax({
                url: '/L_Siembra/backend/data/config/nombres_clases.txt',
                method: 'GET'
            });
            
            // Simplemente dividir por líneas para obtener los nombres
            this.availableNames = response
                .split('\n')
                .map(name => name.trim())
                .filter(name => name.length > 0); // Filtrar líneas vacías

            console.log('Nombres cargados:', this.availableNames); // Debug
                
        } catch (error) {
            this.showError('Error cargando nombres: ' + error.message);
            this.availableNames = [];
        }
    },

    loadExistingAssignments: async function() {
        try {
            const response = await $.ajax({
                url: '/L_Siembra/backend/php/api/class/assignments.php',
                method: 'GET'
            });

            if (response.status === 'success' && response.data) {
                this.assignments = response.data;
                // Actualizar usedNames basado en las asignaciones existentes
                this.usedNames = new Set(Object.values(this.assignments));
                console.log('Asignaciones cargadas:', this.assignments); // Debug
            }
        } catch (error) {
            console.error('Error cargando asignaciones:', error);
        }
    },

    renderUI: function() {
        this.renderNamesPool();
        this.renderEmptyClasses();
    },

    renderNamesPool: function() {
        const namesPool = $('#namesPool');
        namesPool.empty();
        
        // Filtrar nombres que no están asignados
        const unassignedNames = this.availableNames.filter(name => !this.usedNames.has(name));
        
        // Mostrar solo nombres no asignados
        unassignedNames.forEach(name => {
            namesPool.append(this.createNameCard(name));
        });
    },

    renderEmptyClasses: function() {
        const grid = $('#classesGrid');
        grid.empty();
        
        // Crear todas las cajas de clase
        this.PREDEFINED_CLASSES.forEach(className => {
            grid.append(this.createClassBox(className));
        });

        // Mostrar asignaciones existentes
        this.updateAssignments();
    },

    createNameCard: function(name) {
        return `
            <div class="name-card" draggable="true" data-name="${name}">
                ${name}
            </div>
        `;
    },

    createClassBox: function(className) {
        const translatedName = this.CLASS_TRANSLATIONS[className] || className;

        return `
            <div class="class-box empty" data-class="${className}">
                <div class="class-box-header">
                    <div class="class-title">Clase: ${translatedName}</div>
                    <span class="status-indicator status-inactive">Inactiva</span>
                </div>
                <div class="dropzone" data-class="${className}"></div>
            </div>
        `;
    },

    setupEventListeners: function() {
        $('#addNameForm').on('submit', (e) => {
            e.preventDefault();
            this.handleNewName(e);
        });

        $('#saveChanges').click(() => this.saveAssignments());
        $('#continueSetup').click(() => window.location.href = 'setup.html');

        this.setupDragAndDrop();
    },

    setupDragAndDrop: function() {
        // Usar delegación de eventos para las name-cards
        $(document).on('dragstart', '.name-card', (e) => {
            const name = $(e.target).data('name');
            e.originalEvent.dataTransfer.setData('text/plain', name);
            e.originalEvent.dataTransfer.setData('source-class', $(e.target).closest('.dropzone').data('class') || '');
            $(e.target).addClass('dragging');
        });

        $(document).on('dragend', '.name-card', function() {
            $(this).removeClass('dragging');
        });

        // Usar delegación de eventos para las zonas de drop
        $(document).on('dragover', '.dropzone', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave', '.dropzone', function() {
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', '.dropzone', (e) => {
            e.preventDefault();
            const dropzone = $(e.target).closest('.dropzone');
            dropzone.removeClass('drag-over');
            
            const name = e.originalEvent.dataTransfer.getData('text/plain');
            const sourceClass = e.originalEvent.dataTransfer.getData('source-class');
            const targetClass = dropzone.data('class');
            
            // Si viene de otra clase, primero liberar esa asignación
            if (sourceClass) {
                delete this.assignments[sourceClass];
                this.usedNames.delete(name);
            }

            // Si el nombre ya está asignado a otra clase (que no sea la fuente)
            const currentAssignedClass = Object.entries(this.assignments)
                .find(([_, assignedName]) => assignedName === name)?.[0];
            
            if (currentAssignedClass && currentAssignedClass !== sourceClass) {
                this.showError('Esta persona ya está asignada a una clase');
                return;
            }

            // Si la clase destino ya tiene alguien, liberarlo
            if (this.assignments[targetClass]) {
                const currentName = this.assignments[targetClass];
                this.usedNames.delete(currentName);
            }

            // Hacer la nueva asignación
            this.assignNameToClass(name, targetClass);
        });

        // Doble clic para remover asignación
        $(document).on('dblclick', '.dropzone .name-card', (e) => {
            const name = $(e.target).data('name');
            const className = $(e.target).closest('.dropzone').data('class');
            this.removeAssignment(name, className);
        });
    },

    handleNewName: function(e) {
        const newName = $('#newName').val().trim();
        
        if (newName) {
            if (this.availableNames.includes(newName)) {
                this.showError('Este nombre ya existe en la lista');
                return;
            }
            
            this.availableNames.push(newName);
            $('#newName').val('');
            this.renderUI();
            this.showMessage('Nombre agregado exitosamente', 'success');
        }
    },

    assignNameToClass: function(name, className) {
        console.log('Asignando:', { name, className });
        this.assignments[className] = name;
        this.usedNames.add(name);
        
        // Actualizar tanto el pool de nombres como las asignaciones
        this.renderNamesPool();
        this.updateAssignments();
    },

    removeAssignment: function(name, className) {
        delete this.assignments[className];
        this.usedNames.delete(name);
        
        // Actualizar tanto el pool de nombres como las asignaciones
        this.renderNamesPool();
        this.updateAssignments();
        this.showMessage('Asignación removida exitosamente', 'success');
    },

    updateAssignments: function() {
        // Resetear todas las clases
        $('.dropzone').empty();
        $('.class-box')
            .addClass('empty')
            .find('.status-indicator')
            .removeClass('status-active')
            .addClass('status-inactive')
            .text('Inactiva');
        
        // Actualizar clases con asignaciones
        Object.entries(this.assignments).forEach(([className, name]) => {
            if (name) {
                const dropzone = $(`.dropzone[data-class="${className}"]`);
                dropzone.html(this.createNameCard(name));
                
                dropzone.closest('.class-box')
                    .removeClass('empty')
                    .find('.status-indicator')
                    .removeClass('status-inactive')
                    .addClass('status-active')
                    .text('Activa');
            }
        });
    },

    saveAssignments: async function() {
        try {
            // Convertir el objeto de asignaciones a formato adecuado
            const assignmentsToSave = {};
            Object.entries(this.assignments).forEach(([className, name]) => {
                if (name) { // Solo guardar asignaciones válidas
                    assignmentsToSave[className] = name;
                }
            });

            console.log('Guardando asignaciones:', assignmentsToSave);

            const response = await $.ajax({
                url: '/L_Siembra/backend/php/api/class/assignments.php',
                method: 'POST',
                data: { assignments: JSON.stringify(assignmentsToSave) }
            });

            if (response.status === 'success') {
                this.showMessage('Configuración guardada exitosamente', 'success');
                $('#continueSetup').prop('disabled', false);
                
                // Actualizar el estado local
                this.assignments = { ...assignmentsToSave };
                this.usedNames = new Set(Object.values(assignmentsToSave));
                
                // Refrescar la UI
                this.renderUI();
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showError('Error guardando configuración: ' + error.message);
            console.error('Error completo:', error);
        }
    },

    isNameAssigned: function(name) {
        return Object.values(this.assignments).includes(name);
    },

    showMessage: function(message, type = 'success') {
        const messageDiv = $('#errorMessage')
            .removeClass('error success')
            .addClass(type);
        messageDiv.text(message).fadeIn();
        setTimeout(() => messageDiv.fadeOut(), 3000);
    },

    showError: function(message) {
        this.showMessage(message, 'error');
        console.error(message);
    }
};

// Inicialización
$(document).ready(function() {
    ClassManagement.init();
});