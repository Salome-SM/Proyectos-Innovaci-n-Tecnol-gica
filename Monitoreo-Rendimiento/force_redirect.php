<?php
// Desactivar cualquier output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Determinar la ruta base
$base_path = dirname($_SERVER['PHP_SELF']);
$host = $_SERVER['HTTP_HOST'];
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';

// Construir la URL completa
$url = $protocol . '://' . $host . '/L_Siembra/frontend/views/detector.html';

// Limpiar headers anteriores
header_remove();

// Establecer nuevos headers
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Redirigir
header('Location: ' . $url, true, 307);
exit();