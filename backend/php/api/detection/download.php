<?php

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../utils/Response.php';
require_once __DIR__ . '/../../utils/Logger.php';
require_once __DIR__ . 'backend/php/utils/Database.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

use Utils\Response;
use Utils\Logger;
use Utils\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    $logger = Logger::getInstance();
    $logger->info("Iniciando generación de reporte Excel");

    // Verificar si hay detección activa
    $pidFile = ROOT_PATH . '/tmp/detection_pid.txt';
    if (!file_exists($pidFile)) {
        throw new Exception('No hay detección activa');
    }

    // Crear nuevo documento Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte de Detecciones');

    // Obtener datos de la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Obtener resumen diario
    $query = "
        SELECT 
            p.nombre,
            rd.fecha,
            rd.semana,
            rd.dia,
            rd.conteo_total,
            rd.meta_total,
            rd.deficit_total,
            tp.nombre as tipo
        FROM resumen_diario rd
        JOIN personas p ON rd.persona_id = p.id
        JOIN tipos_produccion tp ON rd.tipo_produccion_id = tp.id
        WHERE rd.fecha = CURRENT_DATE
        ORDER BY p.nombre
    ";

    $result = $conn->query($query);

    // Configurar encabezados
    $headers = ['Nombre', 'Fecha', 'Semana', 'Día', 'Conteo Total', 'Meta', 'Déficit', 'Tipo'];
    foreach (range('A', 'H') as $i => $col) {
        $sheet->setCellValue($col . '1', $headers[$i]);
    }

    // Estilo para encabezados
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '002060'], // Color primario
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Llenar datos
    $row = 2;
    while ($data = $result->fetch(PDO::FETCH_ASSOC)) {
        $sheet->setCellValue('A' . $row, $data['nombre']);
        $sheet->setCellValue('B' . $row, $data['fecha']);
        $sheet->setCellValue('C' . $row, $data['semana']);
        $sheet->setCellValue('D' . $row, $data['dia']);
        $sheet->setCellValue('E' . $row, $data['conteo_total']);
        $sheet->setCellValue('F' . $row, $data['meta_total']);
        $sheet->setCellValue('G' . $row, $data['deficit_total']);
        $sheet->setCellValue('H' . $row, $data['tipo']);

        // Estilo condicional para déficit
        if ($data['deficit_total'] > 0) {
            $sheet->getStyle('G' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFCDD2'); // Rojo claro
        }

        $row++;
    }

    // Estilo para datos
    $dataStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($dataStyle);

    // Ajustar ancho de columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Agregar filtros
    $sheet->setAutoFilter('A1:H1');

    // Inmovilizar panel superior
    $sheet->freezePane('A2');

    // Configurar información del documento
    $spreadsheet->getProperties()
        ->setCreator('Sistema l_siembra')
        ->setLastModifiedBy('Sistema l_siembra')
        ->setTitle('Reporte de Detecciones')
        ->setSubject('Reporte diario de detecciones')
        ->setDescription('Reporte generado automáticamente por el sistema de detección')
        ->setCategory('Reporte');

    // Crear archivo temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'reporte_');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    // Enviar archivo
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_detecciones_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    readfile($tempFile);
    unlink($tempFile);
    
    $logger->info("Reporte Excel generado y descargado exitosamente");
    exit;

} catch (Exception $e) {
    $logger->error("Error en generación de reporte Excel", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    if (headers_sent()) {
        echo 'Error al generar reporte: ' . $e->getMessage();
    } else {
        Response::error('Error al generar reporte: ' . $e->getMessage());
    }
}