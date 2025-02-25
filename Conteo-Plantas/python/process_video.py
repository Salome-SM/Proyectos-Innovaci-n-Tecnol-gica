import warnings
import sys
import os
import traceback
from pathlib import Path
import json
import cv2
import numpy as np
from ultralytics import YOLO

def log_message(message):
   print(json.dumps({"message": str(message)}), flush=True)

def log_error(error):
   print(json.dumps({"error": str(error)}), flush=True)

def log_progress(progress, total_plants=None):
   data = {"progress": progress}
   if total_plants is not None:
       data["total_unique_plants"] = total_plants
   print(json.dumps(data), flush=True)

def preprocess_frame(frame, variety):
   """
   Filtro adaptativo según la variedad de planta
   """
   hsv = cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)
   
   # Definir rangos HSV específicos por variedad
   variety_ranges = {
        'white': {
            'lower': np.array([20, 25, 25]),
            'upper': np.array([90, 255, 255])
        },
        'rosse': {  
            'lower': np.array([21, 21, 21]),
            'upper': np.array([92, 255, 255])
        },
        'yellow': {  # Funciona relativamente bien
            'lower': np.array([25, 25, 25]),
            'upper': np.array([95, 255, 255])
        },
        'crimson': {  
            'lower': np.array([20, 8, 18]),
            'upper': np.array([105, 255, 255])
        },
        'orleans_white': {
            'lower': np.array([20, 20, 30]),    
            'upper': np.array([95, 255, 255])   
        }
    }
   
   # Obtener rango para la variedad o usar default
   range_values = variety_ranges.get(variety, variety_ranges['white'])
   mask = cv2.inRange(hsv, range_values['lower'], range_values['upper'])
   
   # Operaciones morfológicas
   kernel_open = np.ones((3,3), np.uint8)
   kernel_close = np.ones((5,5), np.uint8)
   mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, kernel_open, iterations=2)
   mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel_close, iterations=2)
   
   return cv2.bitwise_and(frame, frame, mask=mask)

def count_plants_crossing_line(video_path, model_path, variety, min_area_ratio=0.001):
   """Cuenta plantas que cruzan una línea vertical en el video."""
   try:
       # Asegurar que min_area_ratio sea float
       min_area_ratio = float(min_area_ratio)
       
       # Normalize paths
       video_path = str(Path(video_path).resolve())
       model_path = str(Path(model_path).resolve())
       
       # Verify files exist
       if not os.path.exists(video_path):
           raise FileNotFoundError(f"Video file not found: {video_path}")
       if not os.path.exists(model_path):
           raise FileNotFoundError(f"Model file not found: {model_path}")

       log_message("Loading YOLO model...")
       model = YOLO(model_path)
       
       # Open video capture
       cap = cv2.VideoCapture(video_path)
       if not cap.isOpened():
           raise Exception("Failed to open video file")

       # Get video properties
       total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))
       width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
       height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
       fps = int(cap.get(cv2.CAP_PROP_FPS))

       # Setup output video
       output_dir = Path(video_path).parent / "processed"
       output_dir.mkdir(exist_ok=True)
       output_path = str(output_dir / f"processed_{Path(video_path).stem}.mp4")
       
       fourcc = cv2.VideoWriter_fourcc(*'avc1')
       out = cv2.VideoWriter(output_path, fourcc, fps, (width, height))

       # Tracking variables
       frame_count = 0
       total_unique_plants = 0
       line_position = int(width * 0.08)
       tracked_plants = {}
       counted_plants = set()
       total_frame_area = width * height
       min_area = float(total_frame_area * min_area_ratio)

       while cap.isOpened():
           ret, frame = cap.read()
           if not ret:
               break

           frame_count += 1
           
           # Process frame
           processed_frame = preprocess_frame(frame, variety)
           results = model.predict(processed_frame, verbose=False)

           # Draw counting line
           cv2.line(frame, (line_position, 0), (line_position, height), (0, 255, 255), 2)

           new_tracked_plants = {}

           if len(results) > 0 and results[0].boxes is not None:
               detections = results[0].boxes.data.cpu().numpy()

               for det in detections:
                   x1, y1, x2, y2, conf, class_id = map(float, det)
                   
                   area = float((x2 - x1) * (y2 - y1))
                   if area < min_area:
                       continue

                   x_center = (x1 + x2) / 2
                   y_center = (y1 + y2) / 2

                   plant_id = None
                   min_distance = float('inf')
                   
                   for id, (prev_x, prev_y) in tracked_plants.items():
                       distance = ((x_center - prev_x) ** 2 + (y_center - prev_y) ** 2) ** 0.5
                       if distance < min_distance and distance < 50:
                           min_distance = distance
                           plant_id = id

                   if plant_id is None:
                       plant_id = f"plant_{frame_count}_{len(new_tracked_plants)}"

                   new_tracked_plants[plant_id] = (x_center, y_center)

                   color = (0, 255, 0) if plant_id in counted_plants else (255, 0, 0)
                   cv2.rectangle(frame, (int(x1), int(y1)), (int(x2), int(y2)), color, 2)
                   cv2.putText(frame, f"ID: {plant_id}", (int(x1), int(y1) - 10),
                             cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)

                   if plant_id not in counted_plants:
                       prev_x = tracked_plants.get(plant_id, (x_center, y_center))[0]
                       if prev_x >= line_position and x_center < line_position:
                           total_unique_plants += 1
                           counted_plants.add(plant_id)

           tracked_plants = new_tracked_plants

           cv2.putText(frame, f"Plants counted: {total_unique_plants}", (10, 30),
                      cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 255, 255), 2)

           out.write(frame)

           if frame_count % 10 == 0:
               progress = (frame_count / total_frames) * 100
               log_progress(progress, total_unique_plants)

       # Cleanup
       cap.release()
       out.release()

       # Return results
       result = {
           "complete": True,
           "total_frames": frame_count,
           "total_unique_plants": total_unique_plants,
           "processed_video_path": output_path
       }
       print(json.dumps(result), flush=True)
       return result

   except Exception as e:
       log_error(f"Error processing video: {str(e)}\n{traceback.format_exc()}")
       raise

if __name__ == "__main__":
   try:
       if len(sys.argv) < 8:
           raise ValueError("Insufficient arguments provided")

       video_path = sys.argv[1]
       model_path = sys.argv[2]
       block_number = sys.argv[3]
       bed_number = sys.argv[4]
       year_week = sys.argv[5]
       count_date = sys.argv[6]
       variety = sys.argv[7]

       results = count_plants_crossing_line(
           video_path=video_path,
           model_path=model_path,
           variety=variety,
           min_area_ratio=0.001
       )
       
       results.update({
           "block_number": block_number,
           "bed_number": bed_number,
           "year_week": year_week,
           "count_date": count_date,
           "variety": variety
       })

       print(json.dumps(results), flush=True)

   except Exception as e:
       log_error(f"Fatal error: {str(e)}\n{traceback.format_exc()}")
       sys.exit(1)