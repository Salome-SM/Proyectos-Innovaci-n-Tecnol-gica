"""
Módulo principal de detección de objetos en tiempo real.
"""

import cv2
import numpy as np
import supervision as sv
import time
import logging
import os
import json
import threading
from typing import Dict, List, Tuple, Set
from dataclasses import dataclass
from pathlib import Path

@dataclass
class DetectionConfig:
    """Configuración para el sistema de detección."""
    track_thresh: float = 0.25
    match_thresh: float = 0.8
    frame_rate: int = 30
    track_buffer: int = 30
    conf_threshold: float = 0.5
    window_width: int = 1280
    window_height: int = 720

class FrameProcessor:
    def __init__(self, model, class_counts: Dict[str, int], 
                 counted_ids: Set[int], class_names_dict: Dict[int, str],
                 config: DetectionConfig = None):
        """
        Inicializa el procesador de frames.
        """
        # Inicializar logger primero
        self.logger = logging.getLogger(__name__)
        self.logger.info("Inicializando FrameProcessor")
        
        # Luego el resto de atributos
        self.model = model
        self.class_counts = class_counts
        self.counted_ids = counted_ids
        self.class_names_dict = class_names_dict
        self.config = config or DetectionConfig()
        
        # Cargar nombres después de tener logger
        self.nombre_mapping = self._cargar_nombres()
        
        # Inicializar trackers y anotadores
        self._init_trackers()

    def _init_trackers(self):
        """Inicializa los sistemas de tracking y anotación."""
        try:
            self.byte_tracker = sv.ByteTrack(
                track_thresh=self.config.track_thresh,
                match_thresh=self.config.match_thresh,
                frame_rate=self.config.frame_rate,
                track_buffer=self.config.track_buffer
            )
            
            self.box_annotator = sv.BoxAnnotator(
                thickness=2,
                text_thickness=2,
                text_scale=1
            )
            
            self.logger.info("Trackers inicializados correctamente")
        except Exception as e:
            self.logger.error(f"Error inicializando trackers: {e}")
            raise

    def _cargar_nombres(self) -> Dict[str, str]:
        """
        Carga el mapeo de nombres desde el archivo de configuración.
        
        Returns:
            Dict[str, str]: Mapeo de clases a nombres
        """
        try:
            nombres = {}
            detection_root = os.getenv('DETECTION_ROOT', '.')
            archivo_nombres = os.path.join(detection_root, 'backend/data/config/nombres_clases.txt')
            
            if os.path.exists(archivo_nombres):
                with open(archivo_nombres, 'r', encoding='utf-8') as f:
                    for linea in f:
                        linea = linea.strip()
                        if linea and '=' in linea:
                            clase, nombre = linea.split('=', 1)
                            nombres[clase.strip()] = nombre.strip()
                self.logger.info(f"Nombres cargados: {len(nombres)} registros")
            return nombres
        except Exception as e:
            self.logger.error(f"Error al cargar nombres: {e}")
            return {}

    def procesar_frame(self, frame: np.ndarray) -> np.ndarray:
        try:
            pause_file = os.path.join(os.getenv('DETECTION_ROOT', '.'), 'tmp/detection_paused.txt')
            is_paused = os.path.exists(pause_file)
            
            # Si está pausado, solo mostrar el overlay y retornar
            if is_paused:
                return self._show_pause_overlay(frame)
            
            # Procesamiento normal solo si no está pausado
            results = self.model(frame, conf=self.config.conf_threshold)[0]
            detections = sv.Detections.from_ultralytics(results)
            annotated_frame = frame.copy()
            
            if len(detections) > 0:
                detections = self.byte_tracker.update_with_detections(detections)
                filtered_detections, labels = self._filter_detections(detections)
                
                if len(filtered_detections) > 0:
                    annotated_frame = self.box_annotator.annotate(
                        scene=annotated_frame,
                        detections=filtered_detections,
                        labels=labels
                    )
                    self._update_counts(filtered_detections)
                    self._update_detection_status()
            
            annotated_frame = self._add_info_bar(annotated_frame)
            return annotated_frame
            
        except Exception as e:
            self.logger.error(f"Error al procesar frame: {e}")
            return frame

    def _filter_detections(self, detections: sv.Detections) -> Tuple[sv.Detections, List[str]]:
        """
        Filtra las detecciones para mantener solo las clases autorizadas.
        
        Args:
            detections: Detecciones originales
            
        Returns:
            Tuple[sv.Detections, List[str]]: Detecciones filtradas y etiquetas
        """
        authorized_classes = self._get_authorized_classes()
        valid_detections = []
        valid_labels = []
        seen_classes = {}
        
        for i, (class_id, confidence, tracker_id) in enumerate(
            zip(detections.class_id, detections.confidence, detections.tracker_id)):
            
            class_name = self.class_names_dict[class_id]
            
            if class_name not in authorized_classes:
                continue
                
            if class_name in seen_classes:
                prev_idx, prev_conf = seen_classes[class_name]
                if confidence > prev_conf:
                    # Remover detección anterior
                    valid_detections.remove(prev_idx)
                    valid_labels.pop()
                    # Agregar nueva detección
                    valid_detections.append(i)
                    person_name = self.nombre_mapping.get(class_name, class_name)
                    valid_labels.append(f"{person_name} #{tracker_id}")
                    seen_classes[class_name] = (i, confidence)
            else:
                valid_detections.append(i)
                person_name = self.nombre_mapping.get(class_name, class_name)
                valid_labels.append(f"{person_name} #{tracker_id}")
                seen_classes[class_name] = (i, confidence)
        
        return detections[valid_detections], valid_labels

    def _update_counts(self, detections: sv.Detections):
        """
        Actualiza los conteos de detecciones.
        
        Args:
            detections: Detecciones filtradas
        """
        authorized_classes = self._get_authorized_classes()
        
        for tracker_id, class_id in zip(detections.tracker_id, detections.class_id):
            if tracker_id not in self.counted_ids:
                class_name = self.class_names_dict[class_id]
                if class_name in authorized_classes:
                    self.class_counts[class_name] += 1
                    self.counted_ids.add(tracker_id)
                    person_name = self.nombre_mapping.get(class_name, class_name)
                    self.logger.info(
                        f"Nueva detección - Clase: {class_name}, "
                        f"Persona: {person_name}, ID: {tracker_id}"
                    )

    def _update_detection_status(self):
        """Actualiza el archivo de estado de detección."""
        try:
            detection_root = os.getenv('DETECTION_ROOT', '.')
            status_file = os.path.join(detection_root, 'backend/data/config/detection_status.json')
            
            # Verificar existencia del archivo
            if not os.path.exists(status_file):
                self.logger.error(f"Archivo de estado no encontrado: {status_file}")
                return
            
            try:
                with open(status_file, 'r') as f:
                    status_data = json.load(f)
            except json.JSONDecodeError as e:
                self.logger.error(f"Error decodificando estado actual: {e}")
                return
            
            # Actualizar conteos
            cambios = False
            for class_name, count in self.class_counts.items():
                if class_name in status_data:
                    if status_data[class_name]['current_count'] != count:
                        status_data[class_name]['current_count'] = count
                        cambios = True
                        self.logger.info(f"Actualizado conteo para {class_name}: {count}")
            
            if cambios:
                # Guardar cambios
                try:
                    with open(status_file, 'w') as f:
                        json.dump(status_data, f, indent=2)
                    self.logger.info("Archivo de estado actualizado")
                    
                    # Verificar que se guardó correctamente
                    with open(status_file, 'r') as f:
                        verification = json.load(f)
                        self.logger.info(f"Verificación de estado: {verification}")
                except Exception as e:
                    self.logger.error(f"Error guardando estado: {e}")
            
        except Exception as e:
            self.logger.error(f"Error en _update_detection_status: {e}")

    def _get_authorized_classes(self) -> Set[str]:
        """
        Obtiene las clases autorizadas desde la configuración.
        
        Returns:
            Set[str]: Conjunto de clases autorizadas
        """
        try:
            detection_root = os.getenv('DETECTION_ROOT', '.')
            config_file = os.path.join(detection_root, 'backend/data/config/detection_config.json')
            
            with open(config_file, 'r') as f:
                config = json.load(f)
            
            authorized_classes = set()
            if config['type'] == 'mixed':
                authorized_classes.update(p['class'] for p in config.get('aster', []))
                authorized_classes.update(p['class'] for p in config.get('pompon', []))
            else:
                authorized_classes.update(p['class'] for p in config['selected_persons'])
            
            return authorized_classes
            
        except Exception as e:
            self.logger.error(f"Error al cargar clases autorizadas: {e}")
            return set()

    def _add_info_bar(self, frame: np.ndarray) -> np.ndarray:
        """
        Agrega barra de información al frame.
        
        Args:
            frame: Frame a modificar
            
        Returns:
            np.ndarray: Frame con barra de información
        """
        height, width = frame.shape[:2]
        info_bar = np.zeros((80, width, 3), dtype=np.uint8)
        
        # Mostrar solo conteos de personas autorizadas
        authorized_classes = self._get_authorized_classes()
        text_lines = []
        for class_name, count in self.class_counts.items():
            if class_name in authorized_classes:
                person_name = self.nombre_mapping.get(class_name, class_name)
                text_lines.append(f"{person_name}: {count}")
        
        # Dividir los conteos en dos líneas si hay muchos
        if text_lines:
            mid = len(text_lines) // 2
            line1 = " | ".join(text_lines[:mid])
            line2 = " | ".join(text_lines[mid:])
            
            cv2.putText(info_bar, line1, (10, 25), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
            if line2:
                cv2.putText(info_bar, line2, (10, 50), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
        
        # Advertencia
        cv2.putText(info_bar, 
                   "NO CERRAR - Use 'Detener Detección' en la web", 
                   (10, 70), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
        
        return np.vstack([info_bar, frame])

def procesar_video(cap: cv2.VideoCapture, model, class_counts: Dict[str, int], 
                  counted_ids: Set[int], class_names_dict: Dict[int, str], 
                  stop_event: threading.Event):
    """Procesa el video en tiempo real."""
    try:
        logger = logging.getLogger(__name__)
        logger.info("Iniciando procesamiento de video")
        
        # Log información de la cámara
        logger.info(f"Estado inicial de la cámara: {cap.isOpened()}")
        logger.info(f"Propiedades de la cámara:")
        logger.info(f"- FPS: {cap.get(cv2.CAP_PROP_FPS)}")
        logger.info(f"- Resolución: {cap.get(cv2.CAP_PROP_FRAME_WIDTH)}x{cap.get(cv2.CAP_PROP_FRAME_HEIGHT)}")
        
        processor = FrameProcessor(model, class_counts, counted_ids, class_names_dict)
        
        # Configurar ventana con manejo de errores
        try:
            window_name = 'Detección en tiempo real'
            cv2.namedWindow(window_name, cv2.WINDOW_NORMAL)
            cv2.resizeWindow(window_name, 1280, 720)
            logger.info("Ventana de visualización creada correctamente")
        except Exception as e:
            logger.error(f"Error configurando ventana: {e}")
            raise RuntimeError(f"Error creando ventana de visualización: {e}")
        
        # Crear archivo de señalización
        base_path = os.getenv('DETECTION_ROOT', '.')
        signal_file = os.path.join(base_path, 'tmp/video_active.txt')
        with open(signal_file, 'w') as f:
            f.write('1')

        # Verificar cámara
        if not cap.isOpened():
            logger.error("La cámara no está abierta")
            raise RuntimeError("La cámara no está abierta")

        ret, frame = cap.read()
        if not ret or frame is None:
            logger.error("No se pudo leer el primer frame")
            raise RuntimeError("Error leyendo frame")

        frames_procesados = 0
        tiempo_inicio = time.time()
        
        while not stop_event.is_set():
            # Verificar pausa
            pause_file = os.path.join(base_path, 'tmp/detection_paused.txt')
            is_paused = os.path.exists(pause_file)
            
            # Leer frame
            ret, frame = cap.read()
            if not ret or frame is None:
                logger.warning("Error al leer frame, reintentando...")
                cap.release()
                time.sleep(1)
                cap = cv2.VideoCapture(0)
                if not cap.isOpened():
                    logger.error("No se pudo reabrir la cámara")
                    break
                continue
            
            try:
                if is_paused:
                    frame = _show_pause_overlay(frame)
                else:
                    frame = processor.procesar_frame(frame)
                
                # Mostrar frame
                cv2.imshow(window_name, frame)
                frames_procesados += 1
                
                if frames_procesados % 100 == 0:
                    tiempo_actual = time.time()
                    fps = frames_procesados / (tiempo_actual - tiempo_inicio)
                    logger.info(f"FPS promedio: {fps:.2f}")
                
                # Verificar tecla 'q' para salir (solo en modo debug)
                key = cv2.waitKey(1) & 0xFF
                if key == ord('q') and os.environ.get('DEBUG'):
                    logger.info("Tecla 'q' presionada, terminando")
                    break
                    
            except Exception as e:
                logger.error(f"Error procesando frame: {e}", exc_info=True)
                continue
        
        # Limpieza
        logger.info("Finalizando procesamiento de video")
        cv2.destroyAllWindows()
        cap.release()
        
        try:
            os.remove(signal_file)
            logger.info("Archivo de señalización eliminado")
        except Exception as e:
            logger.warning(f"Error eliminando archivo video_active.txt: {e}")
            
    except Exception as e:
        logger.error(f"Error crítico en procesar_video: {e}", exc_info=True)
        raise
        
def _show_pause_overlay(self, frame: np.ndarray) -> np.ndarray:
        """Muestra un overlay de pausa sobre el frame."""
        height, width = frame.shape[:2]
        overlay = frame.copy()
        
        # Oscurecer el frame
        cv2.rectangle(overlay, (0, 0), (width, height), (0, 0, 0), -1)
        alpha = 0.6
        frame = cv2.addWeighted(overlay, alpha, frame, 1 - alpha, 0)
        
        # Agregar texto de PAUSA
        text = "SISTEMA EN PAUSA"
        font = cv2.FONT_HERSHEY_SIMPLEX
        font_scale = 2
        thickness = 3
        text_size = cv2.getTextSize(text, font, font_scale, thickness)[0]
        
        text_x = (width - text_size[0]) // 2
        text_y = (height + text_size[1]) // 2
        
        # Agregar sombra al texto
        cv2.putText(frame, text, (text_x + 2, text_y + 2), font, font_scale, (0, 0, 0), thickness)
        cv2.putText(frame, text, (text_x, text_y), font, font_scale, (255, 255, 255), thickness)
        
        return frame