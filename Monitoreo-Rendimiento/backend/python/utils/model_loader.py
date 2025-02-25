"""
Utilidades para cargar y gestionar modelos YOLO.
"""

import os
import torch
import logging
from pathlib import Path
import threading
from datetime import datetime, timedelta
from typing import Optional
from dataclasses import dataclass

@dataclass
class ModelConfig:
    """Configuración para la carga de modelos."""
    cache_duration: timedelta = timedelta(hours=24)
    use_cache: bool = True
    force_cpu: bool = False
    fuse_layers: bool = True
    half_precision: bool = True

class ModelCache:
    """
    Gestiona el caché de modelos para mejorar el rendimiento.
    
    Esta clase implementa el patrón Singleton para asegurar una única
    instancia del caché de modelos en toda la aplicación.
    """
    _instance = None
    _lock = threading.Lock()
    
    def __new__(cls):
        with cls._lock:
            if cls._instance is None:
                cls._instance = super(ModelCache, cls).__new__(cls)
                cls._instance._initialized = False
            return cls._instance
    
    def __init__(self):
        """Inicializa el caché de modelos."""
        if self._initialized:
            return
            
        self.cache_dir = Path("python_scripts/cache")
        self.cache_dir.mkdir(parents=True, exist_ok=True)
        self.model = None
        self.last_load_time: Optional[datetime] = None
        self._initialized = True
        
    def get_model(self, model_path: str, config: Optional[ModelConfig] = None) -> torch.nn.Module:
        """
        Obtiene un modelo, ya sea desde el caché o cargándolo nuevamente.
        
        Args:
            model_path: Ruta al archivo del modelo
            config: Configuración opcional para la carga del modelo
            
        Returns:
            torch.nn.Module: Modelo cargado
            
        Raises:
            FileNotFoundError: Si no se encuentra el archivo del modelo
            RuntimeError: Si hay un error al cargar el modelo
        """
        config = config or ModelConfig()
        current_time = datetime.now()
        
        # Verificar si el modelo en caché es válido
        if (self.model is not None and self.last_load_time is not None and 
            current_time - self.last_load_time < config.cache_duration):
            logging.debug("Usando modelo desde memoria")
            return self.model
            
        # Intentar cargar desde caché
        if config.use_cache:
            cache_path = self.cache_dir / "model_cache.pt"
            if cache_path.exists():
                try:
                    logging.info("Intentando cargar modelo desde caché...")
                    self.model = self._load_from_cache(cache_path, config)
                    self.last_load_time = current_time
                    logging.info("Modelo cargado exitosamente desde caché")
                    return self.model
                except Exception as e:
                    logging.warning(f"Error al cargar caché: {e}")
                
        # Cargar desde archivo original
        logging.info("Cargando modelo desde archivo original...")
        self.model = self._load_from_file(model_path, config)
        
        # Guardar en caché
        if config.use_cache:
            try:
                self._save_to_cache(cache_path)
                logging.info("Modelo guardado en caché")
            except Exception as e:
                logging.warning(f"Error al guardar caché: {e}")
            
        self.last_load_time = current_time
        return self.model

    def _load_from_cache(self, cache_path: Path, config: ModelConfig) -> torch.nn.Module:
        """
        Carga el modelo desde el caché.
        
        Args:
            cache_path: Ruta al archivo de caché
            config: Configuración del modelo
            
        Returns:
            torch.nn.Module: Modelo cargado desde caché
        """
        model = torch.load(cache_path, 
                         map_location='cpu' if config.force_cpu else None)
        
        if config.fuse_layers:
            model.fuse()
            
        if config.half_precision and not config.force_cpu:
            model.half()
            
        return model

    def _load_from_file(self, model_path: str, config: ModelConfig) -> torch.nn.Module:
        """
        Carga el modelo desde el archivo original.
        
        Args:
            model_path: Ruta al archivo del modelo
            config: Configuración del modelo
            
        Returns:
            torch.nn.Module: Modelo cargado
            
        Raises:
            FileNotFoundError: Si no se encuentra el archivo
        """
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Archivo de modelo no encontrado: {model_path}")
        
        from ultralytics import YOLO
        model = YOLO(model_path)
        
        if config.fuse_layers:
            model.fuse()
            
        if config.half_precision and not config.force_cpu:
            model.half()
            
        return model

    def _save_to_cache(self, cache_path: Path):
        """
        Guarda el modelo en caché.
        
        Args:
            cache_path: Ruta donde guardar el caché
        """
        torch.save(self.model, cache_path)

def load_yolo_model(model_path: str, config: Optional[ModelConfig] = None) -> torch.nn.Module:
    """
    Función principal para cargar un modelo YOLO.
    
    Args:
        model_path: Ruta al archivo del modelo
        config: Configuración opcional
        
    Returns:
        torch.nn.Module: Modelo YOLO cargado
    """
    return ModelCache().get_model(model_path, config)

def get_device() -> torch.device:
    """
    Determina el dispositivo óptimo para el modelo.
    
    Returns:
        torch.device: Dispositivo a usar (CPU o CUDA)
    """
    if torch.cuda.is_available():
        logging.info("CUDA disponible, usando GPU")
        return torch.device('cuda')
    else:
        logging.info("CUDA no disponible, usando CPU")
        return torch.device('cpu')

def check_model_compatibility(model_path: str) -> bool:
    """
    Verifica la compatibilidad del modelo.
    
    Args:
        model_path: Ruta al archivo del modelo
        
    Returns:
        bool: True si el modelo es compatible
    """
    try:
        model = torch.load(model_path, map_location='cpu')
        required_attributes = ['model', 'names']
        
        for attr in required_attributes:
            if not hasattr(model, attr):
                logging.error(f"Modelo no compatible: falta atributo {attr}")
                return False
                
        return True
    except Exception as e:
        logging.error(f"Error al verificar compatibilidad: {e}")
        return False

def get_model_info(model: torch.nn.Module) -> dict:
    """
    Obtiene información sobre el modelo.
    
    Args:
        model: Modelo cargado
        
    Returns:
        dict: Información del modelo
    """
    return {
        'type': type(model).__name__,
        'device': next(model.parameters()).device.type,
        'num_parameters': sum(p.numel() for p in model.parameters()),
        'num_gradients': sum(p.numel() for p in model.parameters() if p.requires_grad),
        'classes': getattr(model, 'names', {}),
    }