<?php
// exports/export_handler.php
// Main export handler that manages CSV, Excel, and PDF exports
// with chart generation and insights for all segmentation types

// Start output buffering only for PDF (handle later)
// Use error reporting that doesn't suppress warnings/errors
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Check request parameters (support both GET and POST)
$segmentationType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?? 
                    filter_input(INPUT_POST, 'segmentation_type', FILTER_SANITIZE_STRING);
$exportFormat = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING) ?? 
                filter_input(INPUT_POST, 'export_format', FILTER_SANITIZE_STRING);

if (!$segmentationType || !$exportFormat) {
    die(json_encode(['error' => 'Missing required parameters: type and format']));
}

// Validate inputs
$validSegmentations = ['gender', 'region', 'age_group', 'income_bracket', 'cluster', 'purchase_tier', 'clv'];
$validFormats = ['csv', 'excel', 'pdf'];

if (!in_array($segmentationType, $validSegmentations) || !in_array($exportFormat, $validFormats)) {
    die(json_encode(['error' => 'Invalid parameters']));
}

// Directories for charts and exports
$chartDir = __DIR__ . '/../charts';
$exportDir = __DIR__;

if (!is_dir($chartDir)) {
    mkdir($chartDir, 0755, true);
}

// ============================================================================
// FETCH SEGMENTATION DATA
// ============================================================================

function fetchSegmentationData($segmentationType, $pdo) {
    $cluster_metadata = [];
    
    switch ($segmentationType) {
        case 'gender':
            $sql = "SELECT gender, COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income, 
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount 
                    FROM customers GROUP BY gender";
            break;

        case 'region':
            $sql = "SELECT region, COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income, 
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount 
                    FROM customers GROUP BY region ORDER BY total_customers DESC";
            break;

        case 'age_group':
            $sql = "SELECT CASE 
                        WHEN age BETWEEN 18 AND 25 THEN '18-25'
                        WHEN age BETWEEN 26 AND 40 THEN '26-40'
                        WHEN age BETWEEN 41 AND 60 THEN '41-60'
                        ELSE '61+'
                    END AS age_group, COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income, 
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount 
                    FROM customers GROUP BY age_group ORDER BY age_group";
            break;

        case 'income_bracket':
            $sql = "SELECT CASE 
                        WHEN income < 30000 THEN 'Low Income (<30k)'
                        WHEN income BETWEEN 30000 AND 70000 THEN 'Middle Income (30k-70k)'
                        ELSE 'High Income (>70k)'
                    END AS income_bracket, COUNT(*) AS total_customers, ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount 
                    FROM customers GROUP BY income_bracket ORDER BY income_bracket";
            break;

        case 'cluster':
            $sql = "SELECT sr.cluster_label, COUNT(*) AS total_customers, ROUND(AVG(c.income), 2) AS avg_income, 
                    ROUND(AVG(c.purchase_amount), 2) AS avg_purchase_amount, MIN(c.age) AS min_age, MAX(c.age) AS max_age 
                    FROM segmentation_results sr 
                    JOIN customers c ON sr.customer_id = c.customer_id 
                    GROUP BY sr.cluster_label ORDER BY sr.cluster_label";
            
            // Fetch cluster metadata
            try {
                $metadata_sql = "SELECT * FROM cluster_metadata ORDER BY cluster_id";
                $metadata_stmt = $pdo->query($metadata_sql);
                $cluster_metadata = $metadata_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $cluster_metadata = [];
            }
            break;

        case 'purchase_tier':
            $sql = "SELECT CASE 
                        WHEN purchase_amount < 1000 THEN 'Low Spender (<1k)'
                        WHEN purchase_amount BETWEEN 1000 AND 3000 THEN 'Medium Spender (1k-3k)'
                        ELSE 'High Spender (>3k)'
                    END AS purchase_tier, COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income 
                    FROM customers GROUP BY purchase_tier ORDER BY purchase_tier";
            break;

        case 'clv':
            // Customer Lifetime Value (CLV) Segmentation
            $sql = "SELECT
                        COALESCE(clv_tier, 'Bronze') as clv_tier,
                        COUNT(*) AS total_customers,
                        ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount,
                        ROUND(AVG(purchase_frequency), 1) AS avg_frequency,
                        ROUND(AVG(calculated_clv), 2) AS avg_clv,
                        ROUND(AVG(income), 2) AS avg_income,
                        ROUND(AVG(customer_lifespan_months) / 12, 1) AS avg_lifespan_years
                    FROM customers
                    WHERE clv_tier IS NOT NULL OR (calculated_clv IS NOT NULL AND calculated_clv > 0)
                    GROUP BY clv_tier
                    ORDER BY FIELD(clv_tier, 'Platinum', 'Gold', 'Silver', 'Bronze')";
            break;

        default:
            $sql = "SELECT * FROM customers LIMIT 10";
    }

    try {
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['results' => $results, 'cluster_metadata' => $cluster_metadata];
    } catch (PDOException $e) {
        die(json_encode(['error' => 'Database query failed: ' . $e->getMessage()]));
    }
}

// ============================================================================
// FILTER COLUMNS BASED ON SEGMENTATION TYPE
// ============================================================================

function getColumnsForSegmentation($segmentationType) {
    switch ($segmentationType) {
        case 'gender':
            return ['gender', 'total_customers', 'avg_income', 'avg_purchase_amount'];
        case 'region':
            return ['region', 'total_customers', 'avg_income', 'avg_purchase_amount'];
        case 'age_group':
            return ['age_group', 'total_customers', 'avg_income', 'avg_purchase_amount'];
        case 'income_bracket':
            return ['income_bracket', 'total_customers', 'avg_purchase_amount'];
        case 'cluster':
            return ['cluster_label', 'total_customers', 'avg_income', 'avg_purchase_amount', 'min_age', 'max_age'];
        case 'purchase_tier':
            return ['purchase_tier', 'total_customers', 'avg_income'];
        case 'clv':
            return ['clv_tier', 'total_customers', 'avg_purchase_amount', 'avg_frequency', 'avg_clv', 'avg_income', 'avg_lifespan_years'];
        default:
            return array_keys($results[0] ?? []);
    }
}

// ============================================================================
// GENERATE INSIGHTS (Already exists in conversation history)
// ============================================================================

function generateInsights($segmentationType, $results) {
    $insights = '';

    switch ($segmentationType) {
        case 'gender':
            $insights = "Gender-based Analysis:\n\n";
            $totalCustomers = array_sum(array_column($results, 'total_customers'));
            $avgIncome = round(array_sum(array_column($results, 'avg_income')) / count($results), 2);
            $insights .= "• Total customers: " . number_format($totalCustomers) . "\n";
            $insights .= "• Average income across genders: $" . number_format($avgIncome) . "\n";
            $insights .= "• Key finding: Gender distribution shows market diversity with potential for targeted marketing strategies.\n";
            break;

        case 'region':
            $insights = "Regional Analysis:\n\n";
            $topRegion = $results[0];
            $insights .= "• Top region: " . $topRegion['region'] . " with " . number_format($topRegion['total_customers']) . " customers\n";
            $insights .= "• Regional average income: $" . number_format(round(array_sum(array_column($results, 'avg_income')) / count($results), 2)) . "\n";
            $insights .= "• Geographic segmentation reveals regional purchasing patterns and income levels.\n";
            break;

        case 'age_group':
            $insights = "Age Group Analysis:\n\n";
            $totalCustomers = array_sum(array_column($results, 'total_customers'));
            $insights .= "• Total customers analyzed: " . number_format($totalCustomers) . "\n";
            $avgPurchase = round(array_sum(array_column($results, 'avg_purchase_amount')) / count($results), 2);
            $insights .= "• Average purchase amount: $" . number_format($avgPurchase) . "\n";
            $insights .= "• Age-based segmentation enables age-targeted marketing campaigns.\n";
            break;

        case 'income_bracket':
            $insights = "Income Bracket Analysis:\n\n";
            $insights .= "• Multiple income brackets identified: " . implode(", ", array_column($results, 'income_bracket')) . "\n";
            $avgPurchase = round(array_sum(array_column($results, 'avg_purchase_amount')) / count($results), 2);
            $insights .= "• Average purchase amount across brackets: $" . number_format($avgPurchase) . "\n";
            $insights .= "• Income correlation with purchase behavior drives pricing and product strategies.\n";
            break;

        case 'cluster':
            $insights = "Cluster Analysis:\n\n";
            $totalCustomers = array_sum(array_column($results, 'total_customers'));
            $insights .= "• Total customer clusters: " . count($results) . "\n";
            $insights .= "• Total customers segmented: " . number_format($totalCustomers) . "\n";
            $insights .= "• Cluster profiles show distinct customer behavioral patterns and preferences.\n";
            $insights .= "• Each cluster presents unique opportunities for customized marketing strategies.\n";
            break;

        case 'purchase_tier':
            $insights = "Purchase Tier Analysis:\n\n";
            $insights .= "• Purchase tiers identified: " . implode(", ", array_column($results, 'purchase_tier')) . "\n";
            $totalCustomers = array_sum(array_column($results, 'total_customers'));
            $insights .= "• Total customers: " . number_format($totalCustomers) . "\n";
            $insights .= "• Spending patterns vary significantly across customer segments.\n";
            break;

        case 'clv':
            $insights = "Customer Lifetime Value (CLV) Analysis:\n\n";
            $totalCustomers = array_sum(array_column($results, 'total_customers'));
            $insights .= "• Total customers in CLV analysis: " . number_format($totalCustomers) . "\n";
            
            // CLV insights
            $avgClv = round(array_sum(array_column($results, 'avg_clv')) / count($results), 2);
            $insights .= "• Overall average CLV: $" . number_format($avgClv) . "\n";
            
            // Find Platinum customers
            $platinumCustomers = array_filter($results, function($r) { return $r['clv_tier'] === 'Platinum'; });
            if (!empty($platinumCustomers)) {
                $platinum = reset($platinumCustomers);
                $insights .= "• Platinum tier (highest value): " . number_format($platinum['total_customers']) . " customers with avg CLV: $" . number_format($platinum['avg_clv']) . "\n";
            }
            
            // Find Bronze customers
            $bronzeCustomers = array_filter($results, function($r) { return $r['clv_tier'] === 'Bronze'; });
            if (!empty($bronzeCustomers)) {
                $bronze = reset($bronzeCustomers);
                $insights .= "• Bronze tier (entry level): " . number_format($bronze['total_customers']) . " customers - upgrade opportunity\n";
            }
            
            $insights .= "• CLV-based strategy: Focus retention and expansion on high-value tiers\n";
            $insights .= "• Development opportunities: Promote Bronze and Silver tiers to higher value segments\n";
            $insights .= "• Revenue optimization: Maintain Platinum and Gold customer satisfaction and engagement\n";
            $insights .= "• Churn prevention: Implement loyalty programs targeting tier transitions\n";
            $insights .= "• Pricing strategy: Create tier-specific offerings to maximize customer value\n";
            $insights .= "• Resource allocation: Prioritize high-CLV customer support and retention initiatives\n";
            $insights .= "• Growth metrics: Track tier migration rates to measure portfolio health\n";
            break;

        default:
            $insights = "Analysis complete.";
    }

    return $insights;
}

// ============================================================================
// CHART GENERATION
// ============================================================================

function generateCharts($segmentationType, $results, $chartDir, $pdo) {
    $scriptsDir = dirname($chartDir) . '/scripts';
    $nodeExecutable = 'node'; // Assumes node is in PATH

    // Prepare data for bar chart
    if ($segmentationType === 'clv') {
        $keyField = 'clv_tier';
    } elseif ($segmentationType === 'cluster') {
        $keyField = 'cluster_label';
    } elseif ($segmentationType === 'gender') {
        $keyField = 'gender';
    } elseif ($segmentationType === 'region') {
        $keyField = 'region';
    } elseif ($segmentationType === 'age_group') {
        $keyField = 'age_group';
    } elseif ($segmentationType === 'income_bracket') {
        $keyField = 'income_bracket';
    } else {
        $keyField = $segmentationType . '_tier';
    }

    $labels = array_column($results, $keyField);
    $values = array_column($results, 'total_customers');

    // Bar chart data
    $barChartData = [
        'type' => 'bar',
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Total Customers',
            'data' => $values,
            'backgroundColor' => [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(199, 199, 199, 0.8)'
            ],
            'borderColor' => [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(199, 199, 199, 1)'
            ],
            'borderWidth' => 1
        ]],
        'options' => [
            'responsive' => true,
            'plugins' => [
                'legend' => ['display' => true]
            ],
            'scales' => [
                'y' => ['beginAtZero' => true]
            ]
        ]
    ];

    // PIE CHART
    $pieChartData = [
        'type' => 'pie',
        'labels' => $labels,
        'datasets' => [[
            'data' => $values,
            'backgroundColor' => [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ]
        ]],
        'options' => [
            'responsive' => true,
            'plugins' => [
                'legend' => ['position' => 'bottom']
            ]
        ]
    ];

    // Save bar chart JSON and render
    $barJsonFile = $chartDir . '/chart_bar.json';
    file_put_contents($barJsonFile, json_encode($barChartData));
    $barChartFile = $chartDir . '/chart_bar.png';
    exec("$nodeExecutable $scriptsDir/generate_chart.js $barJsonFile $barChartFile 2>&1", $output);

    // Save pie chart JSON and render
    $pieJsonFile = $chartDir . '/chart_pie.json';
    file_put_contents($pieJsonFile, json_encode($pieChartData));
    $pieChartFile = $chartDir . '/chart_pie.png';
    exec("$nodeExecutable $scriptsDir/generate_chart.js $pieJsonFile $pieChartFile 2>&1", $output);

    $charts = [
        'barChart' => $barChartFile,
        'pieChart' => $pieChartFile
    ];

    // ====================================================================
    // CLUSTER-SPECIFIC CHARTS (Radar and Comparison)
    // ====================================================================
    
    if ($segmentationType === 'cluster') {
        // Fetch cluster metadata for radar chart
        try {
            $metadata_sql = "SELECT * FROM cluster_metadata ORDER BY cluster_id";
            $metadata_stmt = $pdo->query($metadata_sql);
            $cluster_metadata = $metadata_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Radar chart - normalized features
            $radarLabels = [];
            $radarDatasets = [];

            // Common metrics for all clusters
            $metrics = ['avg_income', 'avg_purchase_amount', 'customer_count'];

            foreach ($cluster_metadata as $cluster) {
                if (empty($radarLabels)) {
                    $radarLabels = $metrics;
                }

                // Normalize values to 0-100 scale
                $radarData = [];
                foreach ($metrics as $metric) {
                    if ($metric === 'avg_income') {
                        $normalized = min(100, ($cluster['avg_income'] ?? 0) / 1000); // Normalize to 100
                    } elseif ($metric === 'avg_purchase_amount') {
                        $normalized = min(100, ($cluster['avg_purchase_amount'] ?? 0) / 100);
                    } else {
                        $normalized = min(100, ($cluster['customer_count'] ?? 0) / 100);
                    }
                    $radarData[] = $normalized;
                }

                $radarDatasets[] = [
                    'label' => $cluster['cluster_name'] ?? 'Cluster ' . ($cluster['cluster_id'] ?? 'Unknown'),
                    'data' => $radarData,
                    'borderColor' => generateRandomColor(),
                    'backgroundColor' => str_replace('1)', '0.2)', generateRandomColor())
                ];
            }

            $radarChartData = [
                'type' => 'radar',
                'labels' => $radarLabels,
                'datasets' => $radarDatasets,
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => ['position' => 'top']
                    ],
                    'scales' => [
                        'r' => ['beginAtZero' => true, 'max' => 100]
                    ]
                ]
            ];

            $radarJsonFile = $chartDir . '/chart_radar.json';
            file_put_contents($radarJsonFile, json_encode($radarChartData));
            $radarChartFile = $chartDir . '/chart_radar.png';
            exec("$nodeExecutable $scriptsDir/generate_chart.js $radarJsonFile $radarChartFile 2>&1", $output);
            $charts['radarChart'] = $radarChartFile;

            // Comparison chart - grouped bar chart
            $comparisonLabels = array_column($cluster_metadata, 'cluster_name');
            $incomeData = array_column($cluster_metadata, 'avg_income');
            $purchaseData = array_column($cluster_metadata, 'avg_purchase_amount');

            $comparisonChartData = [
                'type' => 'bar',
                'labels' => $comparisonLabels,
                'datasets' => [
                    [
                        'label' => 'Avg Income',
                        'data' => $incomeData,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ],
                    [
                        'label' => 'Avg Purchase Amount',
                        'data' => $purchaseData,
                        'backgroundColor' => 'rgba(75, 192, 192, 0.8)',
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'borderWidth' => 1
                    ]
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => ['display' => true]
                    ],
                    'scales' => [
                        'y' => ['beginAtZero' => true]
                    ]
                ]
            ];

            $comparisonJsonFile = $chartDir . '/chart_comparison.json';
            file_put_contents($comparisonJsonFile, json_encode($comparisonChartData));
            $comparisonChartFile = $chartDir . '/chart_comparison.png';
            exec("$nodeExecutable $scriptsDir/generate_chart.js $comparisonJsonFile $comparisonChartFile 2>&1", $output);
            $charts['comparisonChart'] = $comparisonChartFile;
        } catch (Exception $e) {
            // If cluster_metadata not available, continue without these charts
        }
    }

    // ====================================================================
    // CLV-SPECIFIC CHARTS (Horizontal Bar and Combination)
    // ====================================================================
    
    if ($segmentationType === 'clv') {
        // CLV Tier colors
        $tierColors = [
            'Bronze' => 'rgba(150, 150, 150, 0.8)',
            'Silver' => 'rgba(192, 192, 192, 0.8)',
            'Gold' => 'rgba(255, 215, 0, 0.8)',
            'Platinum' => 'rgba(185, 142, 211, 0.8)'
        ];

        $tierBorders = [
            'Bronze' => 'rgba(100, 100, 100, 1)',
            'Silver' => 'rgba(169, 169, 169, 1)',
            'Gold' => 'rgba(184, 134, 11, 1)',
            'Platinum' => 'rgba(147, 112, 219, 1)'
        ];

        // Horizontal Bar Chart
        $backgroundColor = [];
        foreach ($labels as $label) {
            $backgroundColor[] = $tierColors[$label] ?? 'rgba(100, 100, 100, 0.8)';
        }

        $borderColor = [];
        foreach ($labels as $label) {
            $borderColor[] = $tierBorders[$label] ?? 'rgba(100, 100, 100, 1)';
        }

        $horizontalBarData = [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Customer Count by CLV Tier',
                'data' => $values,
                'backgroundColor' => $backgroundColor,
                'borderColor' => $borderColor,
                'borderWidth' => 2
            ]],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => true]
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true]
                ]
            ]
        ];

        $horizontalJsonFile = $chartDir . '/chart_clv_horizontal.json';
        file_put_contents($horizontalJsonFile, json_encode($horizontalBarData));
        $horizontalChartFile = $chartDir . '/chart_clv_horizontal.png';
        exec("$nodeExecutable $scriptsDir/generate_chart.js $horizontalJsonFile $horizontalChartFile 2>&1", $output);
        $charts['clvHorizontalChart'] = $horizontalChartFile;

        // Combination Chart (Bar + Line with dual y-axes)
        $avgClvValues = array_column($results, 'avg_clv');

        $combinationChartData = [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Customer Count',
                    'data' => $values,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'borderWidth' => 1,
                    'yAxisID' => 'y'
                ],
                [
                    'type' => 'line',
                    'label' => 'Average CLV ($)',
                    'data' => $avgClvValues,
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'yAxisID' => 'y1',
                    'tension' => 0.4
                ]
            ],
            'options' => [
                'responsive' => true,
                'interaction' => ['mode' => 'index', 'intersect' => false],
                'plugins' => [
                    'legend' => ['display' => true]
                ],
                'scales' => [
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Customer Count']
                    ],
                    'y1' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Average CLV ($)'],
                        'grid' => ['drawOnChartArea' => false]
                    ]
                ]
            ]
        ];

        $combinationJsonFile = $chartDir . '/chart_clv_combination.json';
        file_put_contents($combinationJsonFile, json_encode($combinationChartData));
        $combinationChartFile = $chartDir . '/chart_clv_combination.png';
        exec("$nodeExecutable $scriptsDir/generate_chart.js $combinationJsonFile $combinationChartFile 2>&1", $output);
        $charts['clvCombinationChart'] = $combinationChartFile;
    }

    return $charts;
}

// Helper function to generate random colors
function generateRandomColor() {
    $colors = [
        'rgba(54, 162, 235, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(199, 199, 199, 1)',
        'rgba(255, 99, 132, 1)',
        'rgba(201, 203, 207, 1)'
    ];
    return $colors[array_rand($colors)];
}

// ============================================================================
// EXPORT FUNCTIONS
// ============================================================================

function exportToCSV($results, $columns, $segmentationType) {
    $filename = 'segmentation_' . $segmentationType . '_' . date("Ymd_His") . '.csv';
    
    // Clear any buffered output before sending headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    $headerRow = [];
    foreach ($columns as $col) {
        $headerRow[] = ucfirst(str_replace('_', ' ', $col));
    }
    fputcsv($output, $headerRow);
    
    // Write data
    foreach ($results as $row) {
        $csvRow = [];
        foreach ($columns as $col) {
            $csvRow[] = $row[$col] ?? '';
        }
        fputcsv($output, $csvRow);
    }
    
    fclose($output);
    exit;
}

function exportToExcel($results, $columns, $segmentationType, $charts) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($segmentationType);
    
    // Write headers
    $colIndex = 1;
    foreach ($columns as $col) {
        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue($colLetter . '1', ucfirst(str_replace('_', ' ', $col)));
        $colIndex++;
    }
    
    // Make header bold
    $endCol = Coordinate::stringFromColumnIndex($colIndex - 1);
    $headerRange = 'A1:' . $endCol . '1';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFD3D3D3');
    
    // Write data
    $rowIndex = 2;
    foreach ($results as $row) {
        $colIndex = 1;
        foreach ($columns as $col) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . $rowIndex, $row[$col] ?? '');
            $colIndex++;
        }
        $rowIndex++;
    }
    
    // Auto-fit columns
    for ($i = 1; $i < $colIndex; $i++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
    }
    
    // Add charts to Excel - use appropriate charts based on segmentation type
    $chartStartRow = $rowIndex + 2;
    $colIndex = 1;
    
    // For CLV, use CLV-specific charts; for cluster, use all; for others, use standard bar/pie
    $chartsToAdd = [];
    if ($segmentationType === 'clv') {
        // CLV: use horizontal bar, pie, and combination charts (skip standard bar chart)
        $chartsToAdd = ['clvHorizontalChart', 'pieChart', 'clvCombinationChart'];
    } elseif ($segmentationType === 'cluster') {
        // Cluster: use all available charts
        $chartsToAdd = ['barChart', 'pieChart', 'radarChart', 'comparisonChart'];
    } else {
        // Other segmentations: use standard bar and pie charts
        $chartsToAdd = ['barChart', 'pieChart'];
    }
    
    foreach ($chartsToAdd as $chartName) {
        if (isset($charts[$chartName]) && file_exists($charts[$chartName])) {
            $drawing = new Drawing();
            $drawing->setPath($charts[$chartName]);
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $drawing->setCoordinates($colLetter . $chartStartRow);
            $drawing->setWidth(300);
            $drawing->setHeight(200);
            $drawing->setWorksheet($sheet);
            
            $colIndex += 4; // Move to next column for next chart
            if ($colIndex > 26) {
                $chartStartRow += 15;
                $colIndex = 1;
            }
        }
    }
    
    // Save to file
    $filename = 'segmentation_' . $segmentationType . '_' . date("Ymd_His") . '.xlsx';
    $filepath = sys_get_temp_dir() . '/' . $filename;
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($filepath);
    
    // Clear any buffered output before sending headers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    @unlink($filepath);
    exit;
}

function exportToPDF($segmentationType, $results, $columns, $insights, $charts, $cluster_metadata = []) {
    $pdf = new TCPDF('L'); // Landscape
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    
    // Title
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Customer Segmentation Analysis Report - ' . ucfirst(str_replace('_', ' ', $segmentationType)), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Ln(5);
    
    // Insights
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Analysis Insights', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $insights, 0, 'L');
    $pdf->Ln(5);
    
    // Data Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Segmentation Data', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    
    $html = '<table border="1" cellpadding="2" style="border-collapse: collapse; table-layout: fixed; width: 100%"><tr>';
    
    foreach ($columns as $col) {
        $html .= '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $col))) . '</th>';
    }
    $html .= '</tr>';
    
    foreach ($results as $row) {
        $html .= '<tr>';
        foreach ($columns as $col) {
            $value = $row[$col] ?? '';
            $html .= '<td>' . htmlspecialchars(is_numeric($value) ? number_format($value, 2) : $value) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // Charts
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Visualizations', 0, 1, 'L');
    
    if ($segmentationType === 'clv') {
        // CLV specific layout - one chart per page, centered with proper spacing
        
        // Page 1: Horizontal Bar Chart
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'CLV Distribution by Tier', 0, 1, 'C');
        $pdf->Ln(15);
        
        if (isset($charts['clvHorizontalChart']) && file_exists($charts['clvHorizontalChart'])) {
            $imgWidth = 200;
            $imgHeight = 120;
            $pageWidth = $pdf->GetPageWidth();
            $x = ($pageWidth - $imgWidth) / 2; // Center horizontally
            $y = $pdf->GetY();
            $pdf->Image($charts['clvHorizontalChart'], $x, $y, $imgWidth, $imgHeight);
        }
        
        // Page 2: Pie Chart (separate page guaranteed)
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'CLV Tier Distribution Percentage', 0, 1, 'C');
        $pdf->Ln(15);
        
        if (isset($charts['pieChart']) && file_exists($charts['pieChart'])) {
            $imgWidth = 135;
            $imgHeight = 135;
            $pageWidth = $pdf->GetPageWidth();
            $x = ($pageWidth - $imgWidth) / 2; // Center horizontally
            $y = $pdf->GetY();
            $pdf->Image($charts['pieChart'], $x, $y, $imgWidth, $imgHeight);
        }
        
        // Page 3: Combination Chart (separate page guaranteed)
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'CLV Performance Analysis', 0, 1, 'C');
        $pdf->Ln(15);
        
        if (isset($charts['clvCombinationChart']) && file_exists($charts['clvCombinationChart'])) {
            $imgWidth = 220;
            $imgHeight = 130;
            $pageWidth = $pdf->GetPageWidth();
            $x = ($pageWidth - $imgWidth) / 2; // Center horizontally
            $y = $pdf->GetY();
            $pdf->Image($charts['clvCombinationChart'], $x, $y, $imgWidth, $imgHeight);
        }
    } elseif ($segmentationType === 'cluster') {
        // Cluster specific layout
        $chartStartY = $pdf->GetY();
        
        if (isset($charts['barChart']) && file_exists($charts['barChart'])) {
            $pdf->Image($charts['barChart'], 15, $chartStartY, 120, 65);
        }
        
        if (isset($charts['pieChart']) && file_exists($charts['pieChart'])) {
            $pdf->Image($charts['pieChart'], 145, $chartStartY, 120, 65);
        }
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Cluster Feature Profile', 0, 1, 'L');
        
        if (isset($charts['radarChart']) && file_exists($charts['radarChart'])) {
            $pdf->Image($charts['radarChart'], 15, $pdf->GetY(), 240, 120);
        }
        
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Cluster Comparison', 0, 1, 'L');
        
        if (isset($charts['comparisonChart']) && file_exists($charts['comparisonChart'])) {
            $pdf->Image($charts['comparisonChart'], 15, $pdf->GetY(), 240, 100);
        }
    } else {
        // Standard layout
        $chartStartY = $pdf->GetY();
        
        if (isset($charts['barChart']) && file_exists($charts['barChart'])) {
            $pdf->Image($charts['barChart'], 15, $chartStartY, 120, 65);
        }
        
        if (isset($charts['pieChart']) && file_exists($charts['pieChart'])) {
            $pdf->Image($charts['pieChart'], 145, $chartStartY, 120, 65);
        }
    }
    
    // Clear any buffered output before sending PDF
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $filename = 'segmentation_' . $segmentationType . '_' . date("Ymd_His") . '.pdf';
    $filepath = sys_get_temp_dir() . '/' . $filename;
    
    // Save PDF to file first
    $pdf->Output($filepath, 'F');
    
    // Now send it to browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    @unlink($filepath);
    exit;
}

// ============================================================================
// MAIN EXECUTION
// ============================================================================

try {
    // Fetch data
    $data = fetchSegmentationData($segmentationType, $pdo);
    $results = $data['results'];
    $cluster_metadata = $data['cluster_metadata'];
    
    if (empty($results)) {
        die(json_encode(['error' => 'No data found for this segmentation type']));
    }
    
    // Get columns
    $columns = getColumnsForSegmentation($segmentationType);
    
    // Generate insights
    $insights = generateInsights($segmentationType, $results);
    
    // Generate charts
    $charts = generateCharts($segmentationType, $results, $chartDir, $pdo);
    
    // Export based on format
    switch ($exportFormat) {
        case 'csv':
            exportToCSV($results, $columns, $segmentationType);
            break;
        
        case 'excel':
            exportToExcel($results, $columns, $segmentationType, $charts);
            break;
        
        case 'pdf':
            exportToPDF($segmentationType, $results, $columns, $insights, $charts, $cluster_metadata);
            break;
        
        default:
            die(json_encode(['error' => 'Invalid export format']));
    }
} catch (Exception $e) {
    die(json_encode(['error' => 'Export failed: ' . $e->getMessage()]));
}
