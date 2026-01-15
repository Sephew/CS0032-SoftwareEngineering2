<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Function to get color class based on export format
function getFormatColor($format) {
    $format = strtolower($format);
    switch($format) {
        case 'csv':
            return 'primary'; // Blue
        case 'excel':
            return 'success'; // Green
        case 'pdf':
            return 'danger'; // Red
        default:
            return 'secondary';
    }
}

// Function to extract segmentation type from filename
function getSegmentationFromFile($filename) {
    if (preg_match('/export_(\w+)_/', $filename, $matches)) {
        return ucfirst($matches[1]);
    }
    return 'Unknown';
}

// Get all exports sorted by created_at descending
try {
    $sql = "SELECT * FROM exports ORDER BY created_at DESC LIMIT 100";
    $stmt = $pdo->query($sql);
    $exports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $exports = [];
    $error = "Could not fetch export history: " . $e->getMessage();
}

// Handle delete if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $delete_sql = "DELETE FROM exports WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$_POST['delete_id']]);
        header('Location: export_history.php');
        exit;
    } catch (PDOException $e) {
        $error = "Could not delete export: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Export History</h1>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($exports)): ?>
            <div class="alert alert-info" role="alert">
                No exports yet. Start exporting data from the dashboard!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Export Type</th>
                            <th>Format</th>
                            <th>Columns</th>
                            <th>File</th>
                            <th>Date/Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exports as $export): 
                            $formatColor = getFormatColor($export['export_type']);
                            $segmentationType = getSegmentationFromFile($export['export_file']);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($export['id']) ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $segmentationType ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $formatColor ?>">
                                        <?= strtoupper(htmlspecialchars($export['export_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php
                                            $cols = array_slice(explode(',', $export['exported_columns']), 0, 3);
                                            $colCount = count(explode(',', $export['exported_columns']));
                                            echo htmlspecialchars(implode(', ', $cols));
                                            if ($colCount > 3) {
                                                echo " <strong>+".($colCount - 3)."</strong>";
                                            }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <code class="text-secondary"><?= htmlspecialchars($export['export_file']) ?></code>
                                </td>
                                <td>
                                    <small>
                                        <?= date('M d, Y H:i:s', strtotime($export['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this export record?');">
                                        <input type="hidden" name="delete_id" value="<?= $export['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">Statistics</h6>
                    <ul class="list-unstyled">
                        <li><strong>Total Exports:</strong> <?= count($exports) ?></li>
                        <li><strong>Most Common Format:</strong>
                            <?php
                                $formats = array_count_values(array_column($exports, 'export_type'));
                                arsort($formats);
                                $mostCommon = key($formats);
                                echo ucfirst(htmlspecialchars($mostCommon)) . ' (' . $formats[$mostCommon] . ')';
                            ?>
                        </li>
                        <li><strong>Most Common Type:</strong>
                            <?php
                                // Extract type from exported_columns or export_file name if needed
                                $types = [];
                                foreach ($exports as $exp) {
                                    if (preg_match('/export_(\w+)_/', $exp['export_file'], $matches)) {
                                        $types[] = $matches[1];
                                    }
                                }
                                $typeCounts = array_count_values($types);
                                arsort($typeCounts);
                                if (!empty($typeCounts)) {
                                    $mostCommonType = key($typeCounts);
                                    echo ucfirst(htmlspecialchars($mostCommonType)) . ' (' . $typeCounts[$mostCommonType] . ')';
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
