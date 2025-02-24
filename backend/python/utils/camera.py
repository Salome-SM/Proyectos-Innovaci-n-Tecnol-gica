"""
Utilidades para manejo de cámara.
"""

import cv2
import time
import logging
from typing import Tuple, Optional
from dataclasses import dataclass

@dataclass
class CameraConfig:
    """Configuración de la cámara."""
    width: int = 1280
    height: int = 720
    fps: int = 30
    buffer_size: int = 1
    fourcc: str = 'MJPG'
    max_retries: int = 3
    retry_delay: float = 1.0

class CameraInitError(Exception):
    """Error en la inicialización de la cámara."""
    pass

def initialize_camera(index: int = 0, config: Optional[CameraConfig] = None) -> cv2.VideoCapture:
    """
    Inicializa y configura la cámara.
    """
    logger = logging.getLogger(__name__)
    config = config or CameraConfig()
    
    backends = [
        cv2.CAP_DSHOW,  # DirectShow (Windows)
        cv2.CAP_ANY,    # Cualquier backend disponible
        cv2.CAP_V4L2    # Video4Linux2 (Linux)
    ]

    for backend in backends:
        for retry in range(config.max_retries):
            try:
                logger.info(f"Intentando inicializar cámara con backend {backend}, intento {retry + 1}")
                
                # Intentar abrir la cámara
                cap = cv2.VideoCapture(index + backend)
                
                if not cap.isOpened():
                    logger.warning(f"No se pudo abrir la cámara con backend {backend}")
                    continue

                # Configurar propiedades
                properties = [
                    (cv2.CAP_PROP_FRAME_WIDTH, config.width),
                    (cv2.CAP_PROP_FRAME_HEIGHT, config.height),
                    (cv2.CAP_PROP_FPS, config.fps),
                    (cv2.CAP_PROP_BUFFERSIZE, config.buffer_size),
                    (cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*config.fourcc))
                ]

                for prop, value in properties:
                    if not cap.set(prop, value):
                        logger.warning(f"No se pudo establecer la propiedad {prop} a {value}")

                # Verificar que la cámara funciona
                ret, frame = cap.read()
                if ret and frame is not None:
                    logger.info("Cámara inicializada exitosamente")
                    logger.info(f"Resolución: {int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))}x{int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))}")
                    logger.info(f"FPS: {cap.get(cv2.CAP_PROP_FPS)}")
                    return cap
                else:
                    logger.warning("La cámara se abrió pero no se pudo leer un frame")
                    cap.release()

            except Exception as e:
                logger.error(f"Error inicializando cámara: {e}")
                if cap is not None:
                    cap.release()
            
            time.sleep(config.retry_delay)
    
    raise RuntimeError("No se pudo inicializar la cámara después de todos los intentos")
def get_camera_info(cap: cv2.VideoCapture) -> dict:
    """
    Obtiene información sobre la configuración actual de la cámara.
    """
    return {
        'width': int(cap.get(cv2.CAP_PROP_FRAME_WIDTH)),
        'height': int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT)),
        'fps': int(cap.get(cv2.CAP_PROP_FPS)),
        'fourcc': int(cap.get(cv2.CAP_PROP_FOURCC)),
        'buffer_size': int(cap.get(cv2.CAP_PROP_BUFFERSIZE)),
        'backend': int(cap.get(cv2.CAP_PROP_BACKEND))
    }

def verify_camera_working(cap: cv2.VideoCapture, num_test_frames: int = 5) -> bool:
    """
    Verifica que la cámara está funcionando correctamente.
    """
    if not cap.isOpened():
        return False

    for _ in range(num_test_frames):
        ret, frame = cap.read()
        if not ret or frame is None:
            return False
        time.sleep(0.1)

    return True

def log_camera_config(config: dict):
    """
    Registra la configuración de la cámara en el log.
    
    Args:
        config: Configuración de la cámara
    """
    logging.info("Configuración de cámara:")
    for key, value in config.items():
        logging.info(f"  {key}: {value}")

def get_optimal_resolution(cap: cv2.VideoCapture) -> Tuple[int, int]:
    """
    Obtiene la resolución óptima soportada por la cámara.
    
    Args:
        cap: Objeto de captura de video
        
    Returns:
        Tuple[int, int]: Resolución óptima (ancho, alto)
    """
    test_resolutions = [
        (1920, 1080),
        (1280, 720),
        (800, 600),
        (640, 480)
    ]
    
    original_width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    original_height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    
    for width, height in test_resolutions:
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, width)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, height)
        
        actual_width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        actual_height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        
        if actual_width == width and actual_height == height:
            return width, height
    
    # Restaurar configuración original
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, original_width)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, original_height)
    
    return original_width, original_height

def adjust_camera_settings(cap: cv2.VideoCapture, 
                         brightness: Optional[int] = None,
                         contrast: Optional[int] = None,
                         saturation: Optional[int] = None,
                         exposure: Optional[float] = None) -> bool:
    """
    Ajusta la configuración de la cámara.
    
    Args:
        cap: Objeto de captura de video
        brightness: Valor de brillo (0-100)
        contrast: Valor de contraste (0-100)
        saturation: Valor de saturación (0-100)
        exposure: Valor de exposición
        
    Returns:
        bool: True si todos los ajustes fueron exitosos
    """
    success = True
    
    if brightness is not None:
        if not cap.set(cv2.CAP_PROP_BRIGHTNESS, brightness):
            logging.warning(f"No se pudo ajustar el brillo a {brightness}")
            success = False
    
    if contrast is not None:
        if not cap.set(cv2.CAP_PROP_CONTRAST, contrast):
            logging.warning(f"No se pudo ajustar el contraste a {contrast}")
            success = False
    
    if saturation is not None:
        if not cap.set(cv2.CAP_PROP_SATURATION, saturation):
            logging.warning(f"No se pudo ajustar la saturación a {saturation}")
            success = False
    
    if exposure is not None:
        # Desactivar auto-exposición primero
        cap.set(cv2.CAP_PROP_AUTO_EXPOSURE, 0)
        if not cap.set(cv2.CAP_PROP_EXPOSURE, exposure):
            logging.warning(f"No se pudo ajustar la exposición a {exposure}")
            success = False
    
    return success