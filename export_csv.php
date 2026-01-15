<?php
require '../db.php';

$userId = 1;
$columns = $_POST['columns'];
$filters = $_POST['filters'];

$allowedColumns = [
    'segment_id', 'user_name', 'age_group',
    'region', 'segment_label', 'confidence_score'
];

$columns = array_intersect($columns, $allowedColumns);
$columnList = implode(", ", $columns);

$sql = "SELECT $columnList FROM segmentation_results WHERE 1=1";
$params = [];

if (!empty($filters['segment'])) {
    $sql .= " AND segment_label = ?";
    $params[] = $filters['segment'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$filename = "segmentation_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=$filename");

$output = fopen('php://output', 'w');
fputcsv($output, $columns);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);

// log export
$pdo->prepare(
    "INSERT INTO exports (user_id, export_format, exported_columns, filters_applied)
     VALUES (?, 'CSV', ?, ?)"
)->execute([$userId, json_encode($columns), json_encode($filters)]);

exit;
