"""
Módulo para gestión de datos y seguimiento de producción.
"""

import pandas as pd
import numpy as np
import os
import matplotlib.pyplot as plt
from matplotlib.backends.backend_tkagg import FigureCanvasTkAgg
from datetime import datetime, timedelta
import logging
import json
from typing import Dict, Set,  List, Tuple, Optional, Any
from dataclasses import dataclass
import tkinter as tk
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment
import psycopg2
from psycopg2.extras import DictCursor

@dataclass
class ProductionTarget:
    """Configuración de objetivos de producción."""
    aster: int = 25
    pompon: int = 29

@dataclass
class TimeConfig:
    """Configuración de tiempos."""
    hours_per_day: int = 9
    update_interval: int = 2  # segundos

class TimeTracker:
    def __init__(self):
        self.start_time = None
        self.current_period = 0
        self.paused = False
        self.elapsed_at_pause = timedelta(0)
        self.pause_time = None
        self.frozen_remaining = None

    def start(self):
        """Inicia el seguimiento del tiempo."""
        self.start_time = datetime.now()
        self.current_period = 0
        self.paused = False
        self.elapsed_at_pause = timedelta(0)
        self.pause_time = None
        self.frozen_remaining = None

    def pause(self):
        """Pausa el seguimiento del tiempo."""
        if not self.paused and self.start_time:
            self.paused = True
            self.pause_time = datetime.now()
            # Calcular y almacenar el tiempo transcurrido exacto al pausar
            current_elapsed = self.pause_time - self.start_time
            self.elapsed_at_pause = current_elapsed
            # Almacenar los segundos restantes exactos
            total_seconds = current_elapsed.total_seconds()
            hour_seconds = total_seconds % 3600
            self.frozen_remaining = 3600 - hour_seconds
            self.logger.info(f"Sistema pausado - Tiempo restante: {self.frozen_remaining} segundos")

    def resume(self):
        """Reanuda el seguimiento del tiempo."""
        if self.paused and self.pause_time:
            resume_time = datetime.now()
            # Ajustar el tiempo de inicio para mantener el tiempo transcurrido
            pause_duration = resume_time - self.pause_time
            self.start_time = resume_time - self.elapsed_at_pause
            self.paused = False
            self.pause_time = None
            self.frozen_remaining = None
            self.logger.info("Sistema reanudado - Tiempo ajustado")

    def get_time_remaining(self) -> str:
        """Obtiene el tiempo restante en formato MM:SS."""
        if not self.start_time:
            return "No iniciado"

        if self.paused and self.frozen_remaining is not None:
            # Usar el tiempo congelado durante la pausa
            remaining = self.frozen_remaining
        else:
            # Calcular tiempo normal
            elapsed = datetime.now() - self.start_time
            total_seconds = elapsed.total_seconds()
            hour_seconds = total_seconds % 3600
            remaining = 3600 - hour_seconds

        minutes = int(remaining) // 60
        seconds = int(remaining) % 60

        if self.paused:
            return f"{minutes:02d}:{seconds:02d} (PAUSADO)"
        return f"{minutes:02d}:{seconds:02d}"

    def get_elapsed_time(self) -> timedelta:
        """Calcula el tiempo transcurrido."""
        if not self.start_time:
            return timedelta(0)

        if self.paused:
            return self.elapsed_at_pause
            
        return datetime.now() - self.start_time

    def should_increment_period(self) -> bool:
        """Verifica si se debe incrementar el período."""
        if self.paused:
            return False

        elapsed = self.get_elapsed_time()
        new_period = int(elapsed.total_seconds() // 3600)
        if new_period > self.current_period:
            self.current_period = new_period
            return True
        return False

class ProductionTracker:
    """Gestiona el seguimiento de la producción."""
    
    def __init__(self):
        """Inicializa el tracker de producción."""
        self.hourly_counts: Dict[str, Dict[int, int]] = {}
        self.deficits: Dict[str, int] = {}
        self.last_hour_completed: Dict[str, bool] = {}
        self.time_tracker = TimeTracker()
        self.logger = logging.getLogger(__name__)
        self._nombres = self._cargar_nombres()
        self.db_connection = self._init_db_connection()
        self.targets = self._load_targets()

    def _init_db_connection(self):
        """Inicializa la conexión a PostgreSQL."""
        try:
            conn = psycopg2.connect(
                dbname="l_siembra",
                user="postgres",
                password="password",
                host="localhost",
                port="5432"
            )
            self.logger.info("Conexión a base de datos establecida")
            return conn
        except Exception as e:
            self.logger.error(f"Error conectando a base de datos: {e}")
            raise

    def _cargar_nombres(self) -> Dict[str, str]:
        """Carga el mapeo de nombres desde archivo."""
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
                self.logger.info(f"Nombres cargados: {nombres}")
            else:
                self.logger.error(f"Archivo de nombres no encontrado: {archivo_nombres}")
            return nombres
        except Exception as e:
            self.logger.error(f"Error al cargar nombres: {e}")
            return {}

    def _load_targets(self) -> Dict[str, int]:
        """Carga las metas de producción desde la base de datos."""
        try:
            targets = {}
            with self.db_connection.cursor() as cur:
                cur.execute("SELECT clase, tp.meta_por_hora FROM personas p JOIN tipos_produccion tp ON p.tipo_produccion_id = tp.id")
                for clase, meta in cur.fetchall():
                    targets[clase] = meta
            self.logger.info(f"Metas cargadas: {targets}")
            return targets
        except Exception as e:
            self.logger.error(f"Error cargando metas: {e}")
            return {}

    def get_target(self, class_name: str) -> int:
        """Obtiene la meta para una clase específica."""
        return self.targets.get(class_name, 29)  # Default a 29 si no se encuentra

    def update(self, class_counts: Dict[str, int]):
        """Actualiza los conteos y el estado de producción."""
        if self.time_tracker.start_time is None or self.time_tracker.paused:
            return

        try:
            elapsed_time = self.time_tracker.get_elapsed_time()
            current_period = int(elapsed_time.total_seconds() // 3600)

            if current_period > self.time_tracker.current_period:
                self._handle_period_change(class_counts)
                self.time_tracker.current_period = current_period

            for class_name, count in class_counts.items():
                self._update_class_count(class_name, count)
                self._save_to_database(class_name, count, current_period)

        except Exception as e:
            self.logger.error(f"Error en update: {e}")

    def _handle_period_change(self, class_counts: Dict[str, int]):
        """Maneja el cambio de período horario."""
        self.logger.info(f"Cambio de período: {self.time_tracker.current_period} -> {self.time_tracker.current_period + 1}")
        
        for class_name in class_counts.keys():
            current_count = self.get_current_hour_count(class_name)
            target = self.get_target(class_name)
            expected_total = target * (self.time_tracker.current_period + 1)
            
            if current_count < expected_total:
                deficit = expected_total - current_count
                self.deficits[class_name] = deficit
                self.last_hour_completed[class_name] = False
                self.logger.warning(
                    f"{class_name} no alcanzó objetivo. "
                    f"Esperado: {expected_total}, Actual: {current_count}, "
                    f"Déficit: {deficit}"
                )
            else:
                self.deficits[class_name] = 0
                self.last_hour_completed[class_name] = True
                self.logger.info(
                    f"{class_name} completó hora {self.time_tracker.current_period + 1}: "
                    f"{current_count}/{expected_total}"
                )

    def _update_class_count(self, class_name: str, count: int):
        """Actualiza el conteo de una clase específica."""
        if class_name not in self.hourly_counts:
            self.hourly_counts[class_name] = {self.time_tracker.current_period: 0}
            self.last_hour_completed[class_name] = True
        
        self.hourly_counts[class_name][self.time_tracker.current_period] = count
        
        if self.deficits.get(class_name, 0) > 0:
            target = self.get_target(class_name)
            expected_total = target * (self.time_tracker.current_period + 1)
            if count >= expected_total:
                self.deficits[class_name] = 0
                self.last_hour_completed[class_name] = True
                self.logger.info(f"{class_name} recuperó déficit. Nuevo conteo: {count}")

    def _save_to_database(self, class_name: str, count: int, hour: int):
        """Guarda los datos en PostgreSQL."""
        try:
            with self.db_connection.cursor() as cur:
                # Obtener el tipo de producción
                cur.execute("SELECT tipo FROM tipos_produccion tp JOIN personas p ON p.tipo_produccion_id = tp.id WHERE p.clase = %s", (class_name,))
                tipo = cur.fetchone()[0]
                
                # Preparar datos
                fecha = datetime.now().date()
                semana = fecha.isocalendar()[1]
                dia = fecha.strftime("%A")
                meta = self.get_target(class_name)
                deficit = max(0, meta - count)

                # Obtener ID de la persona
                cur.execute("SELECT id FROM personas WHERE clase = %s", (class_name,))
                persona_id = cur.fetchone()
                if not persona_id:
                    self.logger.error(f"Persona no encontrada: {class_name}")
                    return

                # Insertar o actualizar detección
                cur.execute("""
                    INSERT INTO detecciones 
                    (persona_id, fecha, hora, semana, dia, conteo, meta, deficit)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    ON CONFLICT (persona_id, fecha, hora) 
                    DO UPDATE SET 
                        conteo = EXCLUDED.conteo,
                        deficit = EXCLUDED.deficit,
                        fecha_actualizacion = CURRENT_TIMESTAMP
                """, (persona_id[0], fecha, hour, semana, dia, count, meta, deficit))

                # Actualizar resumen diario
                self._update_daily_summary(cur, persona_id[0], fecha, semana, dia)

                self.db_connection.commit()
                self.logger.info(f"Datos guardados en BD para {class_name}")

        except Exception as e:
            self.logger.error(f"Error guardando en base de datos: {e}")
            self.db_connection.rollback()

    def _update_daily_summary(self, cur, persona_id: int, fecha: datetime.date, semana: int, dia: str):
        """Actualiza el resumen diario en la base de datos."""
        try:
            # Calcular totales del día
            cur.execute("""
                SELECT SUM(conteo) as total_conteo,
                       SUM(meta) as total_meta,
                       SUM(deficit) as total_deficit
                FROM detecciones
                WHERE persona_id = %s AND fecha = %s
            """, (persona_id, fecha))
            
            totales = cur.fetchone()
            if not totales:
                return

            # Insertar o actualizar resumen
            cur.execute("""
                INSERT INTO resumen_diario 
                (persona_id, fecha, semana, dia, conteo_total, meta_total, deficit_total)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
                ON CONFLICT (persona_id, fecha) 
                DO UPDATE SET 
                    conteo_total = EXCLUDED.conteo_total,
                    meta_total = EXCLUDED.meta_total,
                    deficit_total = EXCLUDED.deficit_total,
                    fecha_actualizacion = CURRENT_TIMESTAMP
            """, (persona_id, fecha, semana, dia, totales[0], totales[1], totales[2]))

        except Exception as e:
            self.logger.error(f"Error actualizando resumen diario: {e}")
            raise

    def get_current_hour_count(self, class_name: str) -> int:
        """Obtiene el conteo actual para una clase."""
        return self.hourly_counts.get(class_name, {}).get(self.time_tracker.current_period, 0)

    def get_remaining_count(self, class_name: str) -> int:
        """Calcula cuánto falta para alcanzar la meta."""
        target = self.get_target(class_name)
        current = self.get_current_hour_count(class_name)
        return max(0, target - current)

    def is_on_target(self, class_name: str) -> bool:
        """Verifica si se está cumpliendo la meta."""
        return self.get_remaining_count(class_name) == 0

    def __del__(self):
        """Cierra la conexión a la base de datos al destruir el objeto."""
        if hasattr(self, 'db_connection'):
            try:
                self.db_connection.close()
                self.logger.info("Conexión a base de datos cerrada")
            except Exception as e:
                self.logger.error(f"Error cerrando conexión a BD: {e}")

class DataVisualizer:
    def __init__(self, frame):
        self.window = tk.Toplevel()
        self.window.title("Monitor de Rendimiento")
        self.window.geometry("800x600")
        
        # Configurar la figura y el canvas
        self.fig, self.ax = plt.subplots(figsize=(10, 6))
        self.canvas = FigureCanvasTkAgg(self.fig, master=self.window)
        self.canvas_widget = self.canvas.get_tk_widget()
        self.canvas_widget.pack(fill=tk.BOTH, expand=True)
        
        # Crear el overlay de pausa (inicialmente invisible)
        self.pause_overlay = tk.Label(
            self.window,
            text="SISTEMA EN PAUSA",
            font=('Helvetica', 24, 'bold'),
            fg='red',
            bg='white'
        )
        self.pause_overlay.place(relx=0.5, rely=0.5, anchor="center")
        self.pause_overlay.place_forget()  # Ocultar inicialmente

    def update(self, production_tracker, class_counts):
        try:
            is_paused = production_tracker.time_tracker.paused
            self.ax.clear()
            
            if not class_counts:
                self.ax.text(0.5, 0.5, 'Esperando detecciones...', 
                           ha='center', va='center', fontsize=14)
                self.canvas.draw()
                return

            # Ordenar por conteo
            sorted_entries = []
            for class_name, count in class_counts.items():
                if count > 0:
                    real_name = production_tracker._nombres.get(class_name)
                    if not real_name:
                        logging.warning(f"No se encontró nombre para la clase: {class_name}")
                        continue
                        
                    target = production_tracker.get_target(class_name)
                    sorted_entries.append({
                        'class_name': class_name,
                        'real_name': real_name,
                        'count': count,
                        'target': target
                    })

            # Ordenar por conteo de mayor a menor
            sorted_entries.sort(key=lambda x: x['count'], reverse=True)

            if not sorted_entries:
                self.ax.text(0.5, 0.5, 'No hay datos para mostrar', 
                           ha='center', va='center', fontsize=14)
                self.canvas.draw()
                return

            # Preparar datos para el gráfico
            names = [entry['real_name'] for entry in sorted_entries]
            counts = [entry['count'] for entry in sorted_entries]
            targets = [entry['target'] for entry in sorted_entries]
            
            # Crear barras con transparencia si está pausado
            y_pos = np.arange(len(names))
            colors = ['#e74c3c' if count < target else '#2ecc71' 
                     for count, target in zip(counts, targets)]
            alpha = 0.3 if is_paused else 1.0
            
            bars = self.ax.barh(y_pos, counts, align='center', color=colors, alpha=alpha)
            
            # Configurar ejes y etiquetas
            self.ax.set_yticks(y_pos)
            self.ax.set_yticklabels(names, fontsize=12)
            self.ax.tick_params(axis='x', labelsize=11)

            # Líneas de objetivo con transparencia si está pausado
            for i, target in enumerate(targets):
                self.ax.axvline(x=target, color='red', linestyle='--', 
                              alpha=0.2 if is_paused else 0.5)
            
            # Etiquetas en las barras
            for i, bar in enumerate(bars):
                width = bar.get_width()
                deficit = max(0, targets[i] - width)
                label = f'{int(width)}'
                if deficit > 0:
                    label += f' (Faltan: {int(deficit)})'
                self.ax.text(width, bar.get_y() + bar.get_height()/2,
                           label, ha='left', va='center', fontsize=11,
                           alpha=alpha)

            # Título y formato
            current_hour = production_tracker.time_tracker.current_period + 1
            time_remaining = production_tracker.time_tracker.get_time_remaining()
            title = f'Detecciones en Tiempo Real - Hora {current_hour}\nTiempo restante: {time_remaining}'
            
            if is_paused:
                # Agregar texto de PAUSA grande y centrado
                rect = plt.Rectangle((-0.1, -0.1), 1.2, 1.2, 
                                   transform=self.ax.transAxes,
                                   color='white', alpha=0.7,
                                   zorder=1000)
                self.ax.add_patch(rect)
                self.ax.text(0.5, 0.5, 'SISTEMA EN PAUSA',
                           ha='center', va='center',
                           transform=self.ax.transAxes,
                           fontsize=36, color='red',
                           rotation=-30,
                           zorder=1001)
                title += ' [PAUSADO]'
            
            self.ax.set_title(title, fontsize=14, pad=20)
            self.ax.set_xlabel('Conteo', fontsize=12)
            self.ax.grid(axis='x', alpha=0.3 if is_paused else 0.5)
            
            plt.tight_layout()
            self.canvas.draw()
            
            # Actualizar visualización del overlay de pausa
            if is_paused:
                self.pause_overlay.place(relx=0.5, rely=0.5, anchor="center")
                self.window.configure(bg='gray90')  # Oscurecer el fondo
            else:
                self.pause_overlay.place_forget()
                self.window.configure(bg='white')
            
        except Exception as e:
            logging.error(f"Error al actualizar gráfico: {e}", exc_info=True)
            raise