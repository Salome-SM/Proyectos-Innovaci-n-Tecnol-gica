<?php
// Archivo: config/app.php
return [
    'upload_dir' => __DIR__ . '/../storage/uploads',
    'processed_dir' => __DIR__ . '/../storage/processed',
    'excel_dir' => __DIR__ . '/../storage/excel',
    'allowed_types' => [
        'video/mp4',
        'video/quicktime',
        'video/x-quicktime',
        'application/octet-stream' // Permitir este tipo para archivos MOV
    ],
    'max_file_size' => 500 * 1024 * 1024, // 500MB
    'chunk_size' => 1024 * 1024, // 1MB
    'python_path' => '/usr/bin/python3',
    'model_path' => __DIR__ . '/../python/models/best.pt'
];