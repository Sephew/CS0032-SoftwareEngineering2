<?php
require 'export_functions.php';
require __DIR__ . '/vendor/autoload.php';


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

$columns = ['id', 'name', 'score']; // change dynamically if needed
$filters = []; // optional filters

$data = getExportData($columns, $filters);

$filename = __DIR__ . '/export_' . time() . '.csv';
$fp = fopen($filename, 'w');
fputcsv($fp, $columns);

foreach ($data as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

logExport('csv', $columns, $filename);

// Download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
readfile($filename);
exit;
