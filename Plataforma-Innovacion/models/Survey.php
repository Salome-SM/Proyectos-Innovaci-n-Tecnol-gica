<?php
class Survey {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function saveSurvey($userId, $name, $cedula, $sede, $area, $initiativeType, $oportunidad, $impacto, $specificData, $attachmentPath, $fechaIngreso) {
        try {
            $sql = "INSERT INTO surveys (user_id, name, cedula, sede, area, initiative_type, oportunidad, impacto, specific_data, attachment_path, fecha_ingreso) 
                    VALUES (:userId, :name, :cedula, :sede, :area, :initiativeType, :oportunidad, :impacto, :specificData, :attachmentPath, :fechaIngreso)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'userId' => $userId,
                'name' => $name,
                'cedula' => $cedula,
                'sede' => $sede,
                'area' => $area,
                'initiativeType' => $initiativeType,
                'oportunidad' => $oportunidad,
                'impacto' => $impacto,
                'specificData' => json_encode($specificData),
                'attachmentPath' => $attachmentPath,
                'fechaIngreso' => $fechaIngreso
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en saveSurvey: " . $e->getMessage());
            throw new Exception("Error al guardar la encuesta: " . $e->getMessage());
        }
    }

    public function getAllSurveys() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM surveys ORDER BY id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAllSurveys: " . $e->getMessage());
            throw new Exception("Error al obtener las encuestas: " . $e->getMessage());
        }
    }

    public function getSurveyById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM surveys WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getSurveyById: " . $e->getMessage());
            throw new Exception("Error al obtener la encuesta: " . $e->getMessage());
        }
    }

    // En Survey.php, actualizar el método saveRating:
    public function saveRating($surveyId, $userId, $deseable, $impactaEstrategia, $factible, $viable, $weight) {
        try {
            // Convertir valores vacíos a NULL
            $deseable = $deseable === '' ? null : floatval($deseable);
            $impactaEstrategia = $impactaEstrategia === '' ? null : floatval($impactaEstrategia);
            $factible = $factible === '' ? null : floatval($factible);
            $viable = $viable === '' ? null : floatval($viable);
            $weight = $weight === '' ? null : floatval($weight);
    
            $sql = "INSERT INTO survey_ratings (survey_id, user_id, deseable, impacta_estrategia, factible, viable, weight) 
                    VALUES (:survey_id, :user_id, :deseable, :impacta_estrategia, :factible, :viable, :weight)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':survey_id' => $surveyId,
                ':user_id' => $userId,
                ':deseable' => $deseable,
                ':impacta_estrategia' => $impactaEstrategia,
                ':factible' => $factible,
                ':viable' => $viable,
                ':weight' => $weight
            ]);
        } catch (PDOException $e) {
            error_log("Error en saveRating: " . $e->getMessage());
            throw new Exception("Error al guardar la calificación: " . $e->getMessage());
        }
    }
    
    // Corregir el método para actualizar costo
    public function updateCosto($surveyId, $costoAprox) {
        $sql = "UPDATE survey_ratings 
                SET costo_aprox = :costo_aprox 
                WHERE survey_id = :survey_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':survey_id' => $surveyId,
            ':costo_aprox' => $costoAprox
        ]);
    }

    public function getCalificadores($impacto, $area, $oportunidad) {
        // Arrays de áreas para validación
        $areasProduccion = [
            'mipe', 'mirfe', 'labores culturales', 'calidad', 
            'siembra', 'mantenimiento', 'corte', 'exvitro', 'propagación'
        ];
    
        // Inicializar array de calificadores - ahora todos pueden calificar siempre
        $calificadores = [
            'innovaciontr@gmail.com' => [
                'weight' => 0,
                'campos' => ['factible', 'viable']  // Siempre estos campos
            ],
            'gerencia@gmail.com' => [
                'weight' => 0,
                'campos' => ['deseable', 'impacta_estrategia']  // Siempre estos campos
            ],
            'gh@gmail.com' => [
                'weight' => 0,
                'campos' => ['deseable', 'impacta_estrategia']
            ],
            'poscosechaD@gmail.com' => [
                'weight' => 0,
                'campos' => ['deseable', 'impacta_estrategia']
            ],
            'produccionD@gmail.com' => [
                'weight' => 0,
                'campos' => ['deseable', 'impacta_estrategia']
            ]
        ];
    
        // Normalizar inputs
        $oportunidad = strtolower(trim($oportunidad));
        $impacto = strtolower(trim($impacto));
        $area = strtolower(trim($area));
    
        // Asignar pesos según reglas
        // innovaciontr@gmail.com y gerencia@gmail.com siempre pueden calificar
        if ($impacto == 'productividad') {
            if (in_array($oportunidad, $areasProduccion)) {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['produccionD@gmail.com']['weight'] = 0.30;
            }
            elseif ($oportunidad == 'poscosecha') {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['poscosechaD@gmail.com']['weight'] = 0.30;
            }
            elseif ($oportunidad == 'gestion humana') {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['gh@gmail.com']['weight'] = 0.30;
            }
        }
        elseif (in_array($impacto, ['seguridad y salud', 'confort', 'fidelizacion'])) {
            if (in_array($oportunidad, $areasProduccion)) {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.20;
                $calificadores['gerencia@gmail.com']['weight'] = 0.30;
                $calificadores['produccionD@gmail.com']['weight'] = 0.20;
                $calificadores['gh@gmail.com']['weight'] = 0.30;
            }
            elseif ($oportunidad == 'poscosecha') {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.20;
                $calificadores['gerencia@gmail.com']['weight'] = 0.30;
                $calificadores['poscosechaD@gmail.com']['weight'] = 0.20;
                $calificadores['gh@gmail.com']['weight'] = 0.30;
            }
            elseif ($oportunidad == 'gestion humana') {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['gh@gmail.com']['weight'] = 0.30;
            }
        }
        elseif ($impacto == 'innovacion') {
            if (in_array($oportunidad, $areasProduccion)) {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['produccionD@gmail.com']['weight'] = 0.30;
            }
            elseif ($oportunidad == 'poscosecha') {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['poscosechaD@gmail.com']['weight'] = 0.30;
            }
            elseif ($oportunidad == 'gestion humana') {
                $calificadores['innovaciontr@gmail.com']['weight'] = 0.30;
                $calificadores['gerencia@gmail.com']['weight'] = 0.40;
                $calificadores['gh@gmail.com']['weight'] = 0.30;
            }
        }
    
        // Filtrar solo los calificadores que tienen peso asignado
        return array_filter($calificadores, function($data) {
            return $data['weight'] > 0;
        });
    }

    public function getRatingsForSurvey($surveyId) {
        try {
            // Primero obtener la encuesta para poder acceder a sus detalles
            $survey = $this->getSurveyById($surveyId);
            
            $sql = "SELECT sr.deseable, sr.impacta_estrategia, sr.factible, sr.viable, sr.weight, u.email 
                    FROM survey_ratings sr
                    JOIN users u ON sr.user_id = u.id
                    WHERE sr.survey_id = :surveyId";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['surveyId' => $surveyId]);
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($rating) use ($survey) {
                // Obtener los calificadores y sus permisos
                $calificadores = $this->getCalificadores($survey['impacto'], $survey['area'], $survey['oportunidad']);
                $userPermisos = $calificadores[$rating['email']]['campos'] ?? [];
                
                // Filtrar solo los valores que el usuario está autorizado a calificar
                $valores = [];
                if (in_array('deseable', $userPermisos) && $rating['deseable'] !== null) {
                    $valores[] = $rating['deseable'];
                }
                if (in_array('impacta_estrategia', $userPermisos) && $rating['impacta_estrategia'] !== null) {
                    $valores[] = $rating['impacta_estrategia'];
                }
                if (in_array('factible', $userPermisos) && $rating['factible'] !== null) {
                    $valores[] = $rating['factible'];
                }
                if (in_array('viable', $userPermisos) && $rating['viable'] !== null) {
                    $valores[] = $rating['viable'];
                }
                
                // Calcular el promedio solo con los valores permitidos
                $averageRating = !empty($valores) ? array_sum($valores) / count($valores) : 0;
                
                return [
                    'email' => $rating['email'],
                    'deseable' => in_array('deseable', $userPermisos) ? $rating['deseable'] : null,
                    'impacta_estrategia' => in_array('impacta_estrategia', $userPermisos) ? $rating['impacta_estrategia'] : null,
                    'factible' => in_array('factible', $userPermisos) ? $rating['factible'] : null,
                    'viable' => in_array('viable', $userPermisos) ? $rating['viable'] : null,
                    'weight' => $rating['weight'],
                    'average_rating' => round($averageRating, 2),
                    'campos_permitidos' => $userPermisos // Añadimos los campos permitidos
                ];
            }, $ratings);
        } catch (PDOException $e) {
            error_log("Error en getRatingsForSurvey: " . $e->getMessage());
            throw new Exception("Error al obtener las calificaciones: " . $e->getMessage());
        }
    }

    public function getFinalRating($surveyId) {
        try {
            // Obtener la información de la encuesta
            $surveyInfo = $this->getSurveyById($surveyId);
            
            // Obtener los calificadores autorizados y sus pesos
            $calificadores = $this->getCalificadores($surveyInfo['impacto'], $surveyInfo['area'], $surveyInfo['oportunidad']);
            
            // Obtener las calificaciones
            $sql = "SELECT sr.*, u.email 
                    FROM survey_ratings sr
                    JOIN users u ON sr.user_id = u.id
                    WHERE sr.survey_id = :surveyId";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['surveyId' => $surveyId]);
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $weightedSum = 0;
            $totalWeight = 0;
    
            foreach ($ratings as $rating) {
                // Solo procesar si el usuario está en los calificadores autorizados
                if (isset($calificadores[$rating['email']])) {
                    $weight = $calificadores[$rating['email']]['weight'];
                    $camposPermitidos = $calificadores[$rating['email']]['campos'];
                    
                    // Obtener solo los valores de las preguntas que el usuario puede calificar
                    $valores = [];
                    if (in_array('deseable', $camposPermitidos) && $rating['deseable'] !== null) {
                        $valores[] = $rating['deseable'];
                    }
                    if (in_array('impacta_estrategia', $camposPermitidos) && $rating['impacta_estrategia'] !== null) {
                        $valores[] = $rating['impacta_estrategia'];
                    }
                    if (in_array('factible', $camposPermitidos) && $rating['factible'] !== null) {
                        $valores[] = $rating['factible'];
                    }
                    if (in_array('viable', $camposPermitidos) && $rating['viable'] !== null) {
                        $valores[] = $rating['viable'];
                    }
    
                    // Calcular el promedio para este usuario solo con sus preguntas permitidas
                    if (!empty($valores)) {
                        $avgRating = array_sum($valores) / count($valores);
                        $weightedSum += $avgRating * $weight;
                        $totalWeight += $weight;
                    }
                }
            }
    
            return $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;
        } catch (PDOException $e) {
            error_log("Error en getFinalRating: " . $e->getMessage());
            throw new Exception("Error al obtener la calificación final: " . $e->getMessage());
        }
    }
    public function getAllSurveyRatings() {
        try {
            $stmt = $this->pdo->query("
                SELECT s.id as surveyId, sr.user_id, sr.rating, sr.weight, u.email
                FROM surveys s
                LEFT JOIN survey_ratings sr ON s.id = sr.survey_id
                LEFT JOIN users u ON sr.user_id = u.id
                ORDER BY s.id
            ");
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ratingsData = [];
            foreach ($results as $row) {
                if (!isset($ratingsData[$row['surveyId']])) {
                    $ratingsData[$row['surveyId']] = [
                        'surveyId' => $row['surveyId'],
                        'ratings' => [],
                        'finalRating' => 0
                    ];
                }
                
                if ($row['user_id']) {
                    $ratingsData[$row['surveyId']]['ratings'][] = [
                        'email' => $row['email'],
                        'rating' => floatval($row['rating']),
                        'weight' => floatval($row['weight'])
                    ];
                }
            }
            
            foreach ($ratingsData as &$survey) {
                $totalWeightedRating = 0;
                $totalWeight = 0;
                foreach ($survey['ratings'] as $rating) {
                    $totalWeightedRating += $rating['rating'] * $rating['weight'];
                    $totalWeight += $rating['weight'];
                }
                $survey['finalRating'] = $totalWeight > 0 ? $totalWeightedRating / $totalWeight : 0;
            }
            
            return array_values($ratingsData);
        } catch (PDOException $e) {
            error_log("Error en getAllSurveyRatings: " . $e->getMessage());
            throw new Exception("Error al obtener las calificaciones de las encuestas: " . $e->getMessage());
        }
    }

    public function getAllSurveysWithRatings($filters = []) {
        $sql = "SELECT s.*, 
                    COALESCE(AVG((sr.deseable + sr.impacta_estrategia + sr.factible + sr.viable) / 4), 0) as average_rating,
                    (SELECT sr2.costo_aprox 
                        FROM survey_ratings sr2 
                        WHERE sr2.survey_id = s.id 
                        ORDER BY sr2.id DESC 
                        LIMIT 1) as costo_aprox
                FROM surveys s
                LEFT JOIN survey_ratings sr ON s.id = sr.survey_id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['sede'])) {
            $sql .= " AND s.sede = :sede";
            $params[':sede'] = $filters['sede'];
        }
        if (!empty($filters['area'])) {
            $sql .= " AND s.area = :area";
            $params[':area'] = $filters['area'];
        }
        if (!empty($filters['initiative_type'])) {
            $sql .= " AND s.initiative_type = :initiative_type";
            $params[':initiative_type'] = $filters['initiative_type'];
        }
        if (!empty($filters['oportunidad'])) {
            $sql .= " AND s.oportunidad = :oportunidad";
            $params[':oportunidad'] = $filters['oportunidad'];
        }
        if (!empty($filters['impacto'])) {
            $sql .= " AND s.impacto = :impacto";
            $params[':impacto'] = $filters['impacto'];
        }

        $sql .= " GROUP BY s.id, s.name, s.cedula, s.sede, s.area, s.initiative_type, s.oportunidad, 
                s.impacto, s.specific_data, s.attachment_path, s.fecha_ingreso
                ORDER BY s.id DESC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAllSurveysWithRatings: " . $e->getMessage());
            throw new Exception("Error al obtener las encuestas con calificaciones: " . $e->getMessage());
        }
    }
    
    public function getUniqueSedes() {
        $stmt = $this->pdo->query("SELECT DISTINCT sede FROM surveys ORDER BY sede");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getUniqueAreas() {
        $stmt = $this->pdo->query("SELECT DISTINCT area FROM surveys ORDER BY area");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getUniqueOportunidades() {
        $stmt = $this->pdo->query("SELECT DISTINCT oportunidad FROM surveys ORDER BY oportunidad");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getUniqueImpactos() {
        $stmt = $this->pdo->query("SELECT DISTINCT impacto FROM surveys ORDER BY impacto");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getStatsBySede() {
        try {
            $sql = "SELECT 
                        sede,
                        COUNT(*) as total_encuestas,
                        SUM(CASE WHEN initiative_type = 'idea' THEN 1 ELSE 0 END) as ideas,
                        SUM(CASE WHEN initiative_type = 'reto' THEN 1 ELSE 0 END) as retos,
                        SUM(CASE WHEN initiative_type = 'problema' THEN 1 ELSE 0 END) as problemas
                    FROM surveys
                    GROUP BY sede
                    ORDER BY sede";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getStatsBySede: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas por sede: " . $e->getMessage());
        }
    }
    public function getUserRankingData() {
        try {
            $sql = "
                WITH survey_counts AS (
                    SELECT 
                        name,
                        cedula,
                        COUNT(*) as total_ideas
                    FROM surveys 
                    GROUP BY name, cedula
                )
                SELECT 
                    sc.name,
                    sc.cedula,
                    sc.total_ideas,
                    COALESCE(up.participation_points, 0) as participation_points,
                    COALESCE(up.impact_points, 0) as impact_points,
                    up.comments,
                    (sc.total_ideas + COALESCE(up.participation_points, 0) + COALESCE(up.impact_points, 0)) as total_points
                FROM survey_counts sc
                LEFT JOIN user_points up ON sc.cedula = up.user_cedula
                ORDER BY total_points DESC, sc.total_ideas DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getUserRankingData: " . $e->getMessage());
            throw new Exception("Error al obtener datos del ranking: " . $e->getMessage());
        }
    }
    
    public function updateUserPoints($cedula, $participationPoints, $impactPoints, $comments) {
        try {
            $sql = "
                INSERT INTO user_points (user_cedula, participation_points, impact_points, comments)
                VALUES (:cedula, :participationPoints, :impactPoints, :comments)
                ON CONFLICT (user_cedula) DO UPDATE SET
                    participation_points = :participationPoints,
                    impact_points = :impactPoints,
                    comments = :comments,
                    last_updated = CURRENT_TIMESTAMP";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':cedula' => $cedula,
                ':participationPoints' => $participationPoints,
                ':impactPoints' => $impactPoints,
                ':comments' => $comments
            ]);
        } catch (PDOException $e) {
            error_log("Error en updateUserPoints: " . $e->getMessage());
            throw new Exception("Error al actualizar puntos: " . $e->getMessage());
        }
    }
    public function getPointsStats() {
        try {
            $sql = "
                WITH user_stats AS (
                    SELECT 
                        s.name,
                        s.cedula,
                        COUNT(*) as ideas_count,
                        COALESCE(up.participation_points, 0) as participation_points,
                        COALESCE(up.impact_points, 0) as impact_points
                    FROM surveys s
                    LEFT JOIN user_points up ON s.cedula = up.user_cedula
                    GROUP BY s.name, s.cedula, up.participation_points, up.impact_points
                )
                SELECT 
                    name,
                    cedula,
                    ideas_count,
                    participation_points,
                    impact_points,
                    (ideas_count + participation_points + impact_points) as total_points
                FROM user_stats
                ORDER BY total_points DESC
                LIMIT 10";
                
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getPointsStats: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas de puntos: " . $e->getMessage());
        }
    }

    public function getTotalSurveys() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM surveys");
        return $stmt->fetchColumn();
    }

    public function getTotalPrioritizedIdeas() {
        try {
            $sql = "SELECT COUNT(DISTINCT survey_id) FROM survey_ratings 
                    WHERE deseable IS NOT NULL 
                       OR impacta_estrategia IS NOT NULL 
                       OR factible IS NOT NULL 
                       OR viable IS NOT NULL";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en getTotalPrioritizedIdeas: " . $e->getMessage());
            return 0;
        }
    }
}
