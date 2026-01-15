<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

$chartFile = __DIR__ . '/../charts/chart.png';
if (!file_exists($chartFile)) {
    // Create a blank placeholder image (200x100 px)
    $im = imagecreatetruecolor(200, 100);
    $bg = imagecolorallocate($im, 240, 240, 240);
    $textColor = imagecolorallocate($im, 100, 100, 100);
    imagefilledrectangle($im, 0, 0, 200, 100, $bg);
    imagestring($im, 3, 20, 40, 'Chart Missing', $textColor);
    imagepng($im, $chartFile);
    imagedestroy($im);
}

// Fetch data
function getExportData(array $columns, array $filters = []) {
    global $pdo;
    $cols = implode(", ", $columns);
    $sql = "SELECT $cols FROM segmentation_results";

    if (!empty($filters)) {
        $conditions = [];
        foreach ($filters as $col => $value) {
            $conditions[] = "$col = :$col";
        }
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $pdo->prepare($sql);
    foreach ($filters as $col => $value) {
        $stmt->bindValue(":$col", $value);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

// Log export
function logExport($type, $columns, $filePath) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO exports (export_type, exported_columns, export_file) VALUES (:type, :cols, :file)");
    $stmt->execute([
        ':type' => $type,
        ':cols' => implode(",", $columns),
        ':file' => $filePath
    ]);
}
