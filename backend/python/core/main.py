"""
Módulo principal del sistema de detección y seguimiento.
"""

import cv2
import threading
import time
import tkinter as tk
from tkinter import ttk
import sys
import logging
from datetime import datetime
import os
import json
from typing import Dict, Set, Optional
from collections import defaultdict
from pathlib import Path
import numpy as np
import supervision as sv

# Importaciones locales
from core.detection import procesar_video
from core.data_management import ProductionTracker, DataVisualizer
from utils.model_loader import load_yolo_model
from utils.camera import initialize_camera
from utils.logger import setup_logging

class DetectionSystem:
    """Clase principal que maneja el sistema de detección."""
    
    def __init__(self):
        """Inicializa el sistema de detección."""
        self.running = True
        self.class_counts = defaultdict(int)
        self.counted_ids = set()
        self.production_tracker = ProductionTracker()
        self.stop_event = threading.Event()
        self.model = None
        self.camera = None
        self.root = None
        self.video_thread = None
        self.logger = logging.getLogger(__name__)
        self.visualizer = None

    def initialize(self):
        """Inicializa todos los componentes del sistema."""
        try:
            # Configurar logging
            setup_logging()
            self.logger.info("=== Iniciando sistema de detección ===")

            # Verificar y crear directorios necesarios
            self._create_required_directories()

            # Cargar modelo
            self.model = self._load_model()
            self.logger.info("Modelo cargado exitosamente")

            # Inicializar cámara
            self.camera = self._initialize_camera()
            self.logger.info("Cámara inicializada exitosamente")

            # Verificar cámara
            if not self.camera.isOpened():
                self.logger.error("No se pudo abrir la cámara")
                raise RuntimeError("Error de cámara")

            # Verificar frame inicial
            ret, frame = self.camera.read()
            if not ret:
                self.logger.error("No se pudo leer frame inicial")
                raise RuntimeError("Error leyendo frame inicial")

            # Crear archivo de inicialización
            self._create_init_file()

            # Inicializar estado de detección
            self._initialize_detection_status()

            # Configurar y mostrar interfaz gráfica
            self._setup_gui()
            self.logger.info("Interfaz gráfica configurada")

            return True

        except Exception as e:
            self.logger.error(f"Error crítico en inicialización: {e}", exc_info=True)
            return False

    def _create_required_directories(self):
        """Crea los directorios necesarios."""
        dirs = [
            os.path.join(os.getenv('DETECTION_ROOT', '.'), 'backend/data/config'),
            os.path.join(os.getenv('DETECTION_ROOT', '.'), 'backend/logs'),
            os.path.join(os.getenv('DETECTION_ROOT', '.'), 'tmp')
        ]
        for dir_path in dirs:
            os.makedirs(dir_path, exist_ok=True)
            self.logger.info(f"Directorio creado/verificado: {dir_path}")

    def _load_model(self):
        """Carga el modelo YOLO."""
        self.logger.info("Iniciando carga del modelo YOLO...")
        model_path = self._get_model_path()
        
        if not Path(model_path).exists():
            raise FileNotFoundError(f"No se encontró el modelo en: {model_path}")
            
        model = load_yolo_model(model_path)
        self.logger.info("Modelo YOLO cargado exitosamente")
        return model

    def _initialize_camera(self):
        """Inicializa la cámara con múltiples intentos y configuraciones."""
        self.logger.info("Iniciando cámara...")
        
        # Lista de índices de cámara a probar
        camera_indices = [1, -1]  # Probará cámara 0, cámara 1 y default
        # Lista de backends a probar
        backends = [
            cv2.CAP_DSHOW,  # DirectShow (Windows)
            cv2.CAP_ANY,    # Cualquier backend disponible
            cv2.CAP_V4L2    # Video4Linux2 (Linux)
        ]

        for index in camera_indices:
            for backend in backends:
                try:
                    self.logger.info(f"Intentando abrir cámara {index} con backend {backend}")
                    camera = cv2.VideoCapture(index + backend)
                    
                    if camera.isOpened():
                        # Configurar propiedades de la cámara
                        camera.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
                        camera.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
                        camera.set(cv2.CAP_PROP_FPS, 30)
                        camera.set(cv2.CAP_PROP_BUFFERSIZE, 3)

                        # Verificar que la cámara realmente funciona
                        ret, frame = camera.read()
                        if ret and frame is not None:
                            self.logger.info(f"Cámara inicializada exitosamente: índice={index}, backend={backend}")
                            return camera
                        else:
                            camera.release()
                            continue

                except Exception as e:
                    self.logger.warning(f"Error al intentar abrir cámara {index}: {e}")
                    continue

        # Si llegamos aquí, ninguna configuración funcionó
        raise RuntimeError("No se pudo inicializar ninguna cámara")

    def _create_init_file(self):
        """Crea el archivo de inicialización."""
        init_file = os.path.join(os.getenv('DETECTION_ROOT', '.'), 
                               'tmp/detection_initialized.txt')
        with open(init_file, 'w') as f:
            f.write(str(datetime.now()))
        self.logger.info(f"Archivo de inicialización creado: {init_file}")

    def _initialize_detection_status(self):
        """Inicializa o restaura el estado de detección."""
        try:
            config_path = os.path.join(os.getenv('DETECTION_ROOT', '.'), 
                                     'backend/data/config/detection_config.json')
            status_path = os.path.join(os.getenv('DETECTION_ROOT', '.'),
                                     'backend/data/config/detection_status.json')

            # Leer configuración actual
            with open(config_path, 'r') as f:
                config = json.load(f)
                self.logger.info("Configuración cargada correctamente")

            # Crear estado inicial
            initial_status = {}
            if config['type'] == 'mixed':
                for person in config.get('aster', []):
                    initial_status[person['class']] = {
                        'current_count': 0,
                        'target': 25,
                        'deficit': 0,
                        'type': 'aster'
                    }
                for person in config.get('pompon', []):
                    initial_status[person['class']] = {
                        'current_count': 0,
                        'target': 29,
                        'deficit': 0,
                        'type': 'pompon'
                    }
            else:
                target = 25 if config['type'] == 'aster' else 29
                for person in config.get('selected_persons', []):
                    initial_status[person['class']] = {
                        'current_count': 0,
                        'target': target,
                        'deficit': 0,
                        'type': config['type']
                    }

            # Guardar estado inicial
            with open(status_path, 'w') as f:
                json.dump(initial_status, f, indent=2)

            self.logger.info("Estado de detección inicializado correctamente")
            return True

        except Exception as e:
            self.logger.error(f"Error inicializando estado: {e}", exc_info=True)
            return False

    def _setup_gui(self):
        """Configura la interfaz gráfica."""
        self.logger.info("Configurando interfaz gráfica...")
        self.root = tk.Tk()
        self.root.withdraw()  # Esto oculta la ventana principal
        
        frame = self._create_main_frame()
        self.visualizer = DataVisualizer(frame)
        self.logger.info("Interfaz gráfica configurada")

    def _configure_window(self):
        """Configura la ventana principal."""
        self.root.protocol("WM_DELETE_WINDOW", lambda: None)
        
        window_width = 1200
        window_height = 500
        screen_width = self.root.winfo_screenwidth()
        screen_height = self.root.winfo_screenheight()
        center_x = int(screen_width/2 - window_width/2)
        center_y = int(screen_height/2 - window_height/2)
        self.root.geometry(f'{window_width}x{window_height}+{center_x}+{center_y}')
        
        self.root.lift()
        self.root.attributes('-topmost', True)
        self.logger.info("Ventana principal configurada")

    def _create_main_frame(self):
        """Crea el frame principal."""
        frame = ttk.Frame(self.root, padding="10")
        frame.grid(row=0, column=0, sticky=(tk.W, tk.E, tk.N, tk.S))
        self.root.columnconfigure(0, weight=1)
        self.root.rowconfigure(0, weight=1)
        frame.columnconfigure(0, weight=1)
        frame.rowconfigure(0, weight=1)
        return frame

    def _start_processing_thread(self):
        """Inicia el thread de procesamiento de video."""
        self.logger.info("Iniciando thread de procesamiento de video...")
        self.video_thread = threading.Thread(
            target=procesar_video,
            args=(
                self.camera,
                self.model,
                self.class_counts,
                self.counted_ids,
                self.model.names,
                self.stop_event
            ),
            daemon=True
        )
        self.video_thread.start()
        self.logger.info("Thread de procesamiento de video iniciado")

    def _check_stop_file(self):
        """Verifica si existe el archivo de detención."""
        stop_file = os.path.join(
            os.getenv('DETECTION_ROOT', '.'),
            'tmp/stop_detection.txt'
        )
        
        if os.path.exists(stop_file):
            self.logger.info("Archivo de detención detectado")
            try:
                os.remove(stop_file)
                self.running = False
                self.stop_event.set()
                self.root.after(1000, self.root.quit)
                self.logger.info("Proceso de detención iniciado")
            except Exception as e:
                self.logger.error(f"Error al procesar archivo de detención: {e}")
        
        if self.running:
            self.root.after(1000, self._check_stop_file)

    def run(self):
        """Ejecuta el sistema."""
        try:
            # Iniciar thread de procesamiento
            self._start_processing_thread()
            
            # Verificar archivo de detención
            self.root.after(1000, self._check_stop_file)
            self.root.after(1000, self._update_visualization)
            # Iniciar tracking de tiempo
            self.production_tracker.time_tracker.start()
            self.logger.info("Tracking de tiempo iniciado")
            
            # Mantener la ventana abierta
            self.root.mainloop()
            
            # Esperar a que el thread de video termine
            if self.video_thread:
                self.video_thread.join(timeout=5)
            
            # Limpieza final
            if self.camera and self.camera.isOpened():
                self.camera.release()
                self.logger.info("Cámara liberada")
            
            self.logger.info("Programa finalizado correctamente")
            
        except Exception as e:
            self.logger.error(f"Error crítico en ejecución: {e}", exc_info=True)
            raise
        finally:
            self.cleanup()

    def _update_visualization(self):
        if self.running and self.visualizer:
            try:
                # Verificar si está pausado
                pause_file = os.path.join(os.getenv('DETECTION_ROOT', '.'), 'tmp/detection_paused.txt')
                is_paused = os.path.exists(pause_file)
                
                if not is_paused:
                    self.logger.info(f"Actualizando dashboard con conteos: {self.class_counts}")
                    self.visualizer.update(self.production_tracker, self.class_counts)
                else:
                    # En pausa, actualizar solo una vez para mostrar el estado de pausa
                    if not hasattr(self, '_paused_state_shown'):
                        self.visualizer.update(self.production_tracker, self.class_counts)
                        self._paused_state_shown = True

                if self.running:
                    # Ajustar el intervalo de actualización según el estado de pausa
                    update_interval = 5000 if is_paused else 1000  # 5 segundos en pausa, 1 segundo normal
                    self.root.after(update_interval, self._update_visualization)
                    
            except Exception as e:
                self.logger.error(f"Error actualizando visualización: {e}", exc_info=True)

        # Limpiar el estado de pausa mostrado cuando se reanuda
        if not is_paused and hasattr(self, '_paused_state_shown'):
            delattr(self, '_paused_state_shown')

    def cleanup(self):
        """Limpia recursos y archivos temporales."""
        try:
            base_path = os.path.join(os.getenv('DETECTION_ROOT', '.'), 'tmp')
            os.makedirs(base_path, exist_ok=True)
            
            files_to_clean = [
                'detection_pid.txt', 
                'detection_initialized.txt',
                'video_active.txt'
            ]
            
            for file in files_to_clean:
                try:
                    os.remove(os.path.join(base_path, file))
                    self.logger.debug(f"Archivo limpiado: {file}")
                except Exception as e:
                    self.logger.warning(f"Error limpiando archivo {file}: {e}")
                    
            self.logger.info("Limpieza finalizada")
        except Exception as e:
            self.logger.error(f"Error en limpieza: {e}")

    @staticmethod
    def _get_model_path() -> str:
        """Obtiene la ruta del modelo."""
        return os.path.join(
            os.getenv('DETECTION_ROOT', '.'),
            'backend/python/models/bestC.pt'
        )

if __name__ == "__main__":
    system = DetectionSystem()
    
    try:
        # Guardar el PID
        base_path = os.path.join(os.getenv('DETECTION_ROOT', '.'), 'tmp')
        os.makedirs(base_path, exist_ok=True)
        
        pid_file = os.path.join(base_path, 'detection_pid.txt')
        with open(pid_file, 'w') as f:
            f.write(str(os.getpid()))
        
        if system.initialize():
            system.run()
            
    except Exception as e:
        logging.error(f"Error en la ejecución principal: {e}", exc_info=True)
        sys.exit(1)
    finally:
        system.cleanup()