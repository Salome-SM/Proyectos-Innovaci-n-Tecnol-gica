<?php
require_once __DIR__ . '/../models/Survey.php';
require_once __DIR__ . '/../lib/fpdf.php';

// Definición de la clase PDF fuera de cualquier método
class PDF extends FPDF {
    protected $widths;
    protected $aligns;
    protected $lastY;
 
    protected function getStartXY() {
        return [
            'x' => $this->GetX(),
            'y' => ($this->GetY() !== null && $this->GetY() !== false) ? $this->GetY() : $this->lastY
        ];
    }
 
    function Header() {
        $azulCorporativo = [0, 32, 96];
        $amarilloCorporativo = [251, 188, 5];
        
        $this->SetFillColor($azulCorporativo[0], $azulCorporativo[1], $azulCorporativo[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 20, 'Ideas Creativas', 0, 1, 'C', true);
        $this->Ln(5);
 
        $this->SetTextColor(0);
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 10, 'Fecha de generacion: ' . date('d/m/Y H:i'), 0, 1, 'R');
        
        $pageWidth = $this->GetPageWidth() - 20;
        $detailsWidth = $pageWidth * 0.40;
        $remainingWidth = $pageWidth - $detailsWidth;
        
        $this->widths = [
            $remainingWidth * 0.09,
            $remainingWidth * 0.15,
            $remainingWidth * 0.16,
            $remainingWidth * 0.09,
            $remainingWidth * 0.15,
            $remainingWidth * 0.13,
            $remainingWidth * 0.14,
            $remainingWidth * 0.15,
            $detailsWidth
        ];
        
        $this->SetFillColor($amarilloCorporativo[0], $amarilloCorporativo[1], $amarilloCorporativo[2]);
        $this->SetTextColor($azulCorporativo[0], $azulCorporativo[1], $azulCorporativo[2]);
        $this->SetFont('Arial', 'B', 10);
        
        $headers = ['Fecha', 'Nombre', 'Cedula', 'Sede', 'Area', 'Tipo', 'Oportunidad', 'Impacto', 'Detalles'];
        foreach($headers as $i => $header) {
            $this->Cell($this->widths[$i], 12, $header, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->lastY = $this->GetY();
    }
 
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
 
    function SetWidths($w) {
        $this->widths = $w;
    }
 
    function SetAligns($a) {
        $this->aligns = $a;
    }
 
    function CheckPageBreak($h) {
        $startPos = $this->getStartXY();
        if($startPos['y'] + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            $this->lastY = $this->GetY();
        }
    }
 
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb-1] == "\n") $nb--;
        
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ') $sep = $i;
            $l += $cw[$c] ?? 1000;
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
 
    function MultiCellRow($data) {
        $maxHeight = 8;
        $lineHeight = 5;
        $padding = 2;
        
        $heights = array();
        for($i = 0; $i < count($data); $i++) {
            $width = $this->widths[$i] - 2*$padding;
            
            if($i == 8) {
                $this->SetFont('Arial', '', 9);
                $details = explode("\n", $data[$i]);
                $heightNeeded = 0;
                
                foreach($details as $detail) {
                    if(!empty($detail)) {
                        $text = substr($detail, strpos($detail, ':') + 1);
                        $lines = $this->NbLines($width - 20, trim($text));
                        $heightNeeded += $lines * $lineHeight;
                    }
                }
                
                $heights[$i] = $heightNeeded + 6*$padding;
            } else {
                $lines = $this->NbLines($width, $data[$i]);
                $heights[$i] = $lines * $lineHeight + 2*$padding;
            }
            $maxHeight = max($maxHeight, $heights[$i]);
        }
        
        $this->CheckPageBreak($maxHeight);
        
        $startPos = $this->getStartXY();
        $startX = $startPos['x'];
        $startY = $startPos['y'];
        
        for($i = 0; $i < count($data); $i++) {
            $this->SetXY($startX, $startY);
            $this->Rect($startX, $startY, $this->widths[$i], $maxHeight);
            
            if($i == 8) {
                $currentX = $startX + $padding;
                $currentY = $startY + $padding;
                
                $details = explode("\n", $data[$i]);
                foreach($details as $detail) {
                    if(strpos($detail, 'Nombre:') === 0) {
                        $this->SetXY($currentX, $currentY);
                        $this->SetFont('Arial', 'B', 9);
                        $this->Cell(15, $lineHeight, 'Nombre:', 0, 0, 'L');
                        
                        $this->SetFont('Arial', '', 9);
                        $nombre = trim(substr($detail, 7));
                        $this->MultiCell($this->widths[$i] - 2*$padding - 15, $lineHeight,
                            iconv('UTF-8', 'windows-1252', $nombre), 0, 'L');
                        $currentY = $this->lastY = $this->GetY() + $padding;
                    }
                    elseif(strpos($detail, 'Descripción:') === 0) {
                        $this->SetXY($currentX, $currentY);
                        $this->SetFont('Arial', 'B', 9);
                        $this->Cell(20, $lineHeight, 'Descripción:', 0, 0, 'L');
                        
                        $this->SetFont('Arial', '', 9);
                        $descripcion = trim(substr($detail, 12)); 
                        $this->SetX($currentX + 20);
                        $this->MultiCell($this->widths[$i] - 2*$padding - 20, $lineHeight,
                            iconv('UTF-8', 'windows-1252', $descripcion), 0, 'L');
                        $this->lastY = $this->GetY();
                    }
                }
            } else {
                $this->SetFont('Arial', '', 9);
                $y = $startY + ($maxHeight - $heights[$i])/2;
                $this->SetXY($startX + $padding, $y);
                $this->MultiCell($this->widths[$i] - 2*$padding, $lineHeight,
                    iconv('UTF-8', 'windows-1252', $data[$i]), 0, 'C');
            }
            
            $startX += $this->widths[$i];
        }
        
        $this->SetY($startY + $maxHeight);
        $this->lastY = $this->GetY();
    }
 }
class SurveyController {
    private $pdo;
    private $survey;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->survey = new Survey($pdo);
    }

    public function showStats() {
        try {
            $surveys = $this->survey->getAllSurveys();
            $statsBySede = $this->survey->getStatsBySede();
            $pointsStats = $this->survey->getPointsStats(); // Añade esta línea
            require 'views/survey_stats.php';
        } catch (Exception $e) {
            $error = "Error al obtener las estadísticas: " . $e->getMessage();
            require 'views/survey_stats.php';
        }
    }

    public function showSurvey() {
        require 'views/survey.php';
    }

    public function saveSurvey() {
        if (!isset($_SESSION['user_id'])) {
            $error = "Usuario no autenticado";
            require 'views/survey.php';
            return;
        }
    
        $userId = $_SESSION['user_id'];
        $name = $_POST['name'] ?? '';
        $cedula = $_POST['cedula'] ?? '';
        $sede = $_POST['sede'] ?? '';
        $area = $_POST['area'] ?? '';
        $initiativeType = $_POST['initiativeType'] ?? '';
        $oportunidad = $_POST['oportunidad'] ?? '';
        $impacto = $_POST['impacto'] ?? '';
        $fechaIngreso = $_POST['fechaIngreso'] ?? date('Y-m-d H:i:s');
    
        $specificData = $this->getSpecificData($initiativeType);
        $attachmentPath = null;
    
        try {
            $attachmentPath = $this->handleFileUpload();
            
            $result = $this->survey->saveSurvey($userId, $name, $cedula, $sede, $area, $initiativeType, $oportunidad, $impacto, $specificData, $attachmentPath, $fechaIngreso);
            
            if ($result) {
                $message = "Encuesta guardada con éxito";
                if ($attachmentPath) {
                    $message .= " y el archivo se cargó correctamente";
                }
            } else {
                throw new Exception("Error al guardar la encuesta en la base de datos");
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
            error_log("Error en saveSurvey: " . $e->getMessage());
            
            // Si el archivo se cargó pero hubo un error al guardar en la base de datos, eliminamos el archivo
            if ($attachmentPath && file_exists($attachmentPath)) {
                unlink($attachmentPath);
            }
        }
    
        require 'views/survey.php';
    }

    private function getSpecificData($initiativeType) {
        switch ($initiativeType) {
            case 'idea':
                return [
                    'nombreIdea' => $_POST['nombreIdea'] ?? '',
                    'descripcionIdea' => $_POST['descripcionIdea'] ?? ''
                ];
            case 'problema':
                return [
                    'nombreProblema' => $_POST['nombreProblema'] ?? '',
                    'descripcionProblema' => $_POST['descripcionProblema'] ?? ''
                ];
            case 'reto':
                return [
                    'nombreReto' => $_POST['nombreReto'] ?? '',
                    'descripcionReto' => $_POST['descripcionReto'] ?? ''
                ];
            default:
                return [];
        }
    }

    private function handleFileUpload() {
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $uploadDir = 'uploads/';
            
            // Crear el directorio si no existe
            if (!file_exists($uploadDir) && !is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("No se pudo crear el directorio de carga");
                    throw new Exception("No se pudo crear el directorio de carga");
                }
            }
            
            $attachmentPath = $uploadDir . basename($_FILES['attachment']['name']);
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $attachmentPath)) {
                return $attachmentPath;
            } else {
                $errorMessage = "No se pudo mover el archivo cargado";
                $error = error_get_last();
                if ($error !== null) {
                    $errorMessage .= ": " . $error['message'];
                }
                error_log($errorMessage);
                throw new Exception("No se pudo guardar el archivo adjunto. Por favor, inténtelo de nuevo.");
            }
        }
        return null;
    }

    public function listSurveys() {
        try {
            $filters = [
                'sede' => $_GET['sede'] ?? null,
                'area' => $_GET['area'] ?? null,
                'initiative_type' => $_GET['initiative_type'] ?? null,
                'oportunidad' => $_GET['oportunidad'] ?? null,
                'impacto' => $_GET['impacto'] ?? null,
            ];
    
            $surveys = $this->survey->getAllSurveysWithRatings($filters);
            
            // Obtener listas únicas para los filtros
            $sedes = $this->survey->getUniqueSedes();
            $areas = $this->survey->getUniqueAreas();
            $oportunidades = $this->survey->getUniqueOportunidades();
            $impactos = $this->survey->getUniqueImpactos();
    
            require 'views/survey_list.php';
        } catch (Exception $e) {
            $error = "Error al obtener las encuestas: " . $e->getMessage();
            require 'views/survey_list.php';
        }
    }
    // En SurveyController.php agregar:
    public function updateCosto() {
        if ($_SESSION['email'] !== 'innovaciontr@gmail.com') {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
    
        $surveyId = $_POST['surveyId'] ?? null;
        $costoAprox = $_POST['costoAprox'] ?? null;
    
        if (!$surveyId || !$costoAprox) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
    
        try {
            $success = $this->survey->updateCosto($surveyId, $costoAprox);
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function downloadPDF() {
        try {
            $surveys = $this->survey->getAllSurveys();
            
            $pdf = new PDF('L', 'mm', 'A4');
            $pdf->SetMargins(10, 10, 10);
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(true, 15);
            
            foreach ($surveys as $survey) {
                $specificData = json_decode($survey['specific_data'], true);
                
                switch ($survey['initiative_type']) {
                    case 'idea':
                        $details = "Nombre: " . trim($specificData['nombreIdea'] ?? '') . "\n";
                        $details .= "Descripcion: " . trim($specificData['descripcionIdea'] ?? '');
                        break;
                    case 'problema':
                        $details = "Nombre: " . trim($specificData['nombreProblema'] ?? '') . "\n";
                        $details .= "Descripcion: " . trim($specificData['descripcionProblema'] ?? '');
                        break;
                    case 'reto':
                        $details = "Nombre: " . trim($specificData['nombreReto'] ?? '') . "\n";
                        $details .= "Descripcion: " . trim($specificData['descripcionReto'] ?? '');
                        break;
                }
    
                $pdf->MultiCellRow([
                    date('d/m/Y', strtotime($survey['fecha_ingreso'])),
                    trim($survey['name']),
                    trim($survey['cedula']),
                    trim($survey['sede']),
                    trim($survey['area']),
                    trim($survey['initiative_type']),
                    trim($survey['oportunidad']),
                    trim($survey['impacto']),
                    $details
                ]);
            }
    
            $pdf->Output('D', 'reporte_encuestas.pdf');
        } catch (Exception $e) {
            error_log("Error al generar el PDF: " . $e->getMessage());
            throw new Exception("Error al generar el PDF: " . $e->getMessage());
        }
    }
    public function showRateSurveys() {
        try {
            $surveys = $this->survey->getAllSurveysWithRatings();
            $userEmail = $_SESSION['email'] ?? '';
            
            // Obtener las listas para los filtros
            $sedes = $this->survey->getUniqueSedes();
            $areas = $this->survey->getUniqueAreas();
            $oportunidades = $this->survey->getUniqueOportunidades();
            $impactos = $this->survey->getUniqueImpactos();
    
            $uniqueSurveys = [];
            foreach ($surveys as $survey) {
                if (!isset($uniqueSurveys[$survey['id']])) {
                    $calificadores = $this->survey->getCalificadores($survey['impacto'], $survey['area'], $survey['oportunidad']);
                    $survey['canRate'] = isset($calificadores[$userEmail]);
                    $survey['ratings'] = $this->survey->getRatingsForSurvey($survey['id']);
                    $uniqueSurveys[$survey['id']] = $survey;
                }
            }
    
            $uniqueSurveys = array_values($uniqueSurveys);
            require 'views/rate_surveys.php';
        } catch (Exception $e) {
            $error = "Error al obtener las encuestas para calificar: " . $e->getMessage();
            require 'views/rate_surveys.php';
        }
    }
    public function getRatingForm() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['surveyId'])) {
            echo json_encode(['error' => 'Acceso no autorizado o ID de encuesta no proporcionado']);
            exit;
        }
    
        $surveyId = $_GET['surveyId'];
        try {
            $survey = $this->survey->getSurveyById($surveyId);
            if (!$survey) {
                echo json_encode(['error' => 'Encuesta no encontrada']);
                exit;
            }
            
            // Preparar las variables necesarias para la vista
            $calificadores = $this->survey->getCalificadores($survey['impacto'], $survey['area'], $survey['oportunidad']);
            
            // Incluir la vista con las variables necesarias
            require 'views/rating_form.php';
            
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener la encuesta: ' . $e->getMessage()]);
            exit;
        }
    }

    public function saveRating() {
        header('Content-Type: application/json');
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception("Usuario no autenticado");
            }
    
            $surveyId = $_POST['surveyId'] ?? null;
            $userId = $_SESSION['user_id'];
            $userEmail = $_SESSION['email'];
    
            if (!$surveyId) {
                throw new Exception(message: "ID de encuesta no proporcionado");
            }
    
            $survey = $this->survey->getSurveyById($surveyId);
            $calificadores = $this->survey->getCalificadores($survey['impacto'], $survey['area'], $survey['oportunidad']);
            
            if (!isset($calificadores[$userEmail])) {
                throw new Exception("No está autorizado para calificar esta encuesta");
            }
    
            $userPermisos = $calificadores[$userEmail];
            $weight = $userPermisos['weight'];
            $camposPermitidos = $userPermisos['campos'];
    
            // Verificar y procesar solo los campos permitidos
            $deseable = in_array('deseable', $camposPermitidos) ? ($_POST['deseable'] ?? null) : null;
            $impactaEstrategia = in_array('impacta_estrategia', $camposPermitidos) ? ($_POST['impactaEstrategia'] ?? null) : null;
            $factible = in_array('factible', $camposPermitidos) ? ($_POST['factible'] ?? null) : null;
            $viable = in_array('viable', $camposPermitidos) ? ($_POST['viable'] ?? null) : null;
    
            $success = $this->survey->saveRating($surveyId, $userId, $deseable, $impactaEstrategia, $factible, $viable, $weight);
    
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Calificación guardada con éxito',
                    'ratings' => $this->survey->getRatingsForSurvey($surveyId),
                    'finalRating' => $this->survey->getFinalRating($surveyId)
                ]);
            } else {
                throw new Exception("Error al guardar la calificación");
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getRatings() {
        if (!isset($_GET['surveyId'])) {
            echo "Error: No se proporcionó el ID de la encuesta.";
            return;
        }

        $surveyId = $_GET['surveyId'];
        try {
            $ratings = $this->survey->getRatingsForSurvey($surveyId);
            $finalRating = $this->survey->getFinalRating($surveyId);
            
            include 'views/ratings_display.php';
        } catch (Exception $e) {
            echo "Error al obtener las calificaciones: " . $e->getMessage();
        }
    }
    public function viewRanking() {
        try {
            $rankings = $this->survey->getUserRankingData();
            require 'views/view_ranking.php';
        } catch (Exception $e) {
            $error = "Error al obtener el ranking: " . $e->getMessage();
            require 'views/view_ranking.php';
        }
    }
    
    public function updatePoints() {
        if ($_SESSION['email'] !== 'innovaciontr@gmail.com') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
    
        try {
            $cedula = $_POST['cedula'] ?? '';
            $participationPoints = intval($_POST['participationPoints'] ?? 0);
            $impactPoints = intval($_POST['impactPoints'] ?? 0);
            $comments = $_POST['comments'] ?? '';
    
            $success = $this->survey->updateUserPoints($cedula, $participationPoints, $impactPoints, $comments);
            
            if ($success) {
                $rankings = $this->survey->getUserRankingData();
                echo json_encode(['success' => true, 'data' => $rankings]);
            } else {
                throw new Exception("Error al actualizar los puntos");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getRatingDetails() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['surveyId'])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado o ID de encuesta no proporcionado']);
            exit;
        }
    
        $surveyId = $_GET['surveyId'];
        try {
            $ratings = $this->survey->getRatingsForSurvey($surveyId);
            $finalRating = $this->survey->getFinalRating($surveyId);
            echo json_encode([
                'success' => true,
                'ratings' => $ratings,
                'finalRating' => $finalRating
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    private function calculateSectionAverage($values) {
        $sum = array_sum($values);
        $count = count(array_filter($values, function($v) { return $v > 0; }));
        return $count > 0 ? $sum / $count : 0;
    }

    public function showPrioritizeSurveys() {
        try {
            $surveys = $this->survey->getAllSurveysWithRatings();
            $surveysWithDetails = [];
            
            foreach ($surveys as $survey) {
                $survey['ratings'] = $this->survey->getRatingsForSurvey($survey['id']);
                $survey['finalRating'] = $this->survey->getFinalRating($survey['id']);
                $surveysWithDetails[] = $survey;
            }
            
            require 'views/prioritize_surveys.php';
        } catch (Exception $e) {
            $error = "Error al obtener las encuestas para priorizar: " . $e->getMessage();
            require 'views/prioritize_surveys.php';
        }
    }
}

