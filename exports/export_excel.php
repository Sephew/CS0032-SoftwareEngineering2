<?php
require 'export_functions.php';
require __DIR__ . '/vendor/autoload.php';


$columns = ['id', 'name', 'score'];
$filters = [];

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

$data = getExportData($columns, $filters);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$colIndex = 1;
foreach ($columns as $col) {
    $sheet->setCellValueByColumnAndRow($colIndex++, 1, strtoupper($col));
}

// Data
$rowIndex = 2;
foreach ($data as $row) {
    $colIndex = 1;
    foreach ($columns as $col) {
        $sheet->setCellValueByColumnAndRow($colIndex++, $rowIndex, $row[$col]);
    }
    $rowIndex++;
}

// Optional chart image
$chartFile = __DIR__ . '/../charts/chart.png';
if (file_exists($chartFile)) {
    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing->setPath($chartFile);
    $drawing->setCoordinates('E2');
    $drawing->setWorksheet($sheet);
}

$filename = __DIR__ . '/export_' . time() . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

logExport('excel', $columns, $filename);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
readfile($filename);
exit;
