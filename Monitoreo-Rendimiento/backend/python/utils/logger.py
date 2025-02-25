"""
Configuración y utilidades de logging.
"""

import logging
import os
from datetime import datetime
from pathlib import Path
from typing import Optional
from dataclasses import dataclass

@dataclass
class LogConfig:
    """Configuración para el sistema de logging."""
    level: int = logging.INFO
    format: str = '%(asctime)s - %(levelname)s - %(message)s'
    date_format: str = '%Y-%m-%d %H:%M:%S'
    file_prefix: str = 'detection'
    max_files: int = 5
    max_size: int = 10 * 1024 * 1024  # 10 MB

def setup_logging(config: Optional[LogConfig] = None) -> None:
    """
    Configura el sistema de logging.
    
    Args:
        config: Configuración opcional del logger
    """
    config = config or LogConfig()
    
    # Crear directorio de logs
    log_dir = Path(os.getenv('DETECTION_ROOT', '.')) / 'logs'
    log_dir.mkdir(exist_ok=True)
    
    # Generar nombre de archivo de log
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    log_file = log_dir / f"{config.file_prefix}_{timestamp}.log"
    
    # Configurar formato
    formatter = logging.Formatter(
        fmt=config.format,
        datefmt=config.date_format
    )
    
    # Configurar handler de archivo
    file_handler = logging.FileHandler(log_file, encoding='utf-8')
    file_handler.setFormatter(formatter)
    
    # Configurar handler de consola
    console_handler = logging.StreamHandler()
    console_handler.setFormatter(formatter)
    
    # Configurar logger raíz
    root_logger = logging.getLogger()
    root_logger.setLevel(config.level)
    
    # Limpiar handlers existentes
    root_logger.handlers.clear()
    
    # Agregar nuevos handlers
    root_logger.addHandler(file_handler)
    root_logger.addHandler(console_handler)
    
    # Limpiar logs antiguos
    cleanup_old_logs(log_dir, config)
    
    logging.info("Sistema de logging inicializado")

def cleanup_old_logs(log_dir: Path, config: LogConfig) -> None:
    """
    Limpia logs antiguos.
    
    Args:
        log_dir: Directorio de logs
        config: Configuración del logger
    """
    try:
        log_files = sorted(
            [f for f in log_dir.glob(f"{config.file_prefix}_*.log")],
            key=lambda x: x.stat().st_mtime,
            reverse=True
        )
        
        # Mantener solo los archivos más recientes
        for file in log_files[config.max_files:]:
            try:
                file.unlink()
                logging.debug(f"Log antiguo eliminado: {file}")
            except Exception as e:
                logging.warning(f"Error al eliminar log antiguo {file}: {e}")
        
        # Verificar tamaño de archivos restantes
        for file in log_files[:config.max_files]:
            if file.stat().st_size > config.max_size:
                try:
                    archive_log(file)
                except Exception as e:
                    logging.warning(f"Error al archivar log {file}: {e}")
                    
    except Exception as e:
        logging.error(f"Error en limpieza de logs: {e}")

def archive_log(log_file: Path) -> None:
    """
    Archiva un archivo de log comprimiéndolo.
    
    Args:
        log_file: Archivo a archivar
    """
    import gzip
    import shutil
    
    archive_path = log_file.with_suffix('.log.gz')
    with log_file.open('rb') as f_in:
        with gzip.open(archive_path, 'wb') as f_out:
            shutil.copyfileobj(f_in, f_out)
    
    log_file.unlink()
    logging.debug(f"Log archivado: {archive_path}")

def get_current_log_file() -> Optional[Path]:
    """
    Obtiene la ruta del archivo de log actual.
    
    Returns:
        Optional[Path]: Ruta al archivo de log actual
    """
    root_logger = logging.getLogger()
    for handler in root_logger.handlers:
        if isinstance(handler, logging.FileHandler):
            return Path(handler.baseFilename)
    return None

def add_file_handler(file_path: Path, 
                    level: int = logging.DEBUG,
                    format_str: Optional[str] = None) -> None:
    """
    Agrega un nuevo handler de archivo al logger.
    
    Args:
        file_path: Ruta al nuevo archivo de log
        level: Nivel de logging
        format_str: Formato opcional para los mensajes
    """
    handler = logging.FileHandler(file_path)
    handler.setLevel(level)
    
    if format_str:
        formatter = logging.Formatter(format_str)
        handler.setFormatter(formatter)
    
    logging.getLogger().addHandler(handler)
    logging.debug(f"Nuevo handler agregado: {file_path}")

def set_log_level(level: int) -> None:
    """
    Cambia el nivel de logging.
    
    Args:
        level: Nuevo nivel de logging
    """
    logging.getLogger().setLevel(level)
    logging.info(f"Nivel de logging cambiado a: {logging.getLevelName(level)}")

class LoggerContext:
    """Contexto para modificar temporalmente la configuración del logger."""
    
    def __init__(self, level: Optional[int] = None, 
                 format_str: Optional[str] = None):
        self.level = level
        self.format_str = format_str
        self.old_level = None
        self.old_handlers = []
        
    def __enter__(self):
        logger = logging.getLogger()
        
        # Guardar configuración actual
        self.old_level = logger.level
        self.old_handlers = logger.handlers.copy()
        
        # Aplicar nueva configuración
        if self.level is not None:
            logger.setLevel(self.level)
            
        if self.format_str is not None:
            formatter = logging.Formatter(self.format_str)
            for handler in logger.handlers:
                handler.setFormatter(formatter)
                
        return logger
        
    def __exit__(self, exc_type, exc_val, exc_tb):
        logger = logging.getLogger()
        
        # Restaurar configuración anterior
        if self.old_level is not None:
            logger.setLevel(self.old_level)
            
        logger.handlers = self.old_handlers