<?php
// api/index.php - RESTful API Router
// Main entry point for all API requests

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../db.php';

// API Version
define('API_VERSION', '1.0');

// Extract request path and method
$request_method = $_SERVER['REQUEST_METHOD'];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_path = str_replace('/csapp/api', '', $request_path);
$request_path = trim($request_path, '/');

// Parse query parameters
$query_params = $_GET;

// Parse JSON body if POST/PUT
$request_body = null;
if (in_array($request_method, ['POST', 'PUT'])) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $request_body = json_decode($raw_input, true);
    }
}

// Generate request ID
$request_id = 'req_' . substr(md5(uniqid()), 0, 12);

// ============================================================================
// ROUTING
// ============================================================================

try {
    // Health check (no auth required)
    if ($request_path === 'health' && $request_method === 'GET') {
        handleHealth();
        exit;
    }
    
    // Authentication
    if ($request_path === 'auth/login' && $request_method === 'POST') {
        handleLogin($request_body, $request_id);
        exit;
    }
    
    // Verify authentication for other endpoints
    $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $user_id = 1; // Mock user for now
    
    // TODO: Uncomment this when ready to enable authentication
    /*
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = substr($auth_header, 7);
        // TODO: Verify JWT token
        $user_id = 1; // Mock user for now
    } else {
        // No token provided
        if ($request_path !== 'health') {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid authentication token',
                'request_id' => $request_id
            ]);
            exit;
        }
    }
    */
    
    // Route segments endpoints
    if (preg_match('#^segments/(.+)$#', $request_path, $matches) && $request_method === 'GET') {
        $segment_type = $matches[1];
        handleGetSegments($segment_type, $query_params, $request_id, $pdo);
        exit;
    }
    
    // Route clusters endpoints
    if ($request_path === 'clusters' && $request_method === 'GET') {
        handleListClusters($query_params, $request_id, $pdo);
        exit;
    }
    
    if ($request_path === 'clusters/run' && $request_method === 'POST') {
        handleRunClustering($request_body, $request_id);
        exit;
    }
    
    // Route customer endpoints
    if (preg_match('#^customers/(\d+)/segment$#', $request_path, $matches) && $request_method === 'GET') {
        $customer_id = $matches[1];
        handleGetCustomerSegment($customer_id, $query_params, $request_id, $pdo);
        exit;
    }
    
    // Route insights endpoints
    if (preg_match('#^insights/(.+)$#', $request_path, $matches) && $request_method === 'GET') {
        $insight_type = $matches[1];
        handleGetInsights($insight_type, $request_id, $pdo);
        exit;
    }
    
    // Route export endpoints
    if (preg_match('#^export/(.+)$#', $request_path, $matches) && $request_method === 'POST') {
        $export_type = $matches[1];
        handleRequestExport($export_type, $request_body, $request_id);
        exit;
    }
    
    // 404 Not Found
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Not Found',
        'message' => "Endpoint '$request_path' not found",
        'request_id' => $request_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'request_id' => $request_id
    ]);
}

// ============================================================================
// HANDLER FUNCTIONS
// ============================================================================

function handleHealth() {
    global $request_id;
    $response = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'components' => [
            'database' => 'connected',
            'cache' => 'operational',
            'api_version' => API_VERSION
        ]
    ];
    http_response_code(200);
    echo json_encode($response);
}

function handleLogin($request_body, $request_id) {
    // Mock authentication - replace with real auth
    $username = $request_body['username'] ?? null;
    $password = $request_body['password'] ?? null;
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bad Request',
            'message' => 'Missing username or password',
            'request_id' => $request_id
        ]);
        return;
    }
    
    // TODO: Validate against actual user database
    // For now, mock token generation
    $payload = [
        'user_id' => 1,
        'username' => $username,
        'iat' => time(),
        'exp' => time() + 3600
    ];
    
    // Mock JWT token (in production, use proper JWT library)
    $token = base64_encode(json_encode($payload));
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'metadata' => [
            'request_id' => $request_id,
            'timestamp' => date('c'),
            'api_version' => API_VERSION
        ]
    ]);
}

function handleGetSegments($segment_type, $query_params, $request_id, $pdo) {
    $limit = isset($query_params['limit']) ? min((int)$query_params['limit'], 500) : 50;
    $offset = isset($query_params['offset']) ? (int)$query_params['offset'] : 0;
    $sort_by = $query_params['sort_by'] ?? 'total_customers';
    $sort_order = strtoupper($query_params['sort_order'] ?? 'DESC');
    
    // Validate segmentation type
    $valid_types = ['gender', 'region', 'age_group', 'income_bracket', 'cluster', 'purchase_tier', 'clv'];
    if (!in_array($segment_type, $valid_types)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bad Request',
            'message' => "Invalid segmentation type: $segment_type",
            'request_id' => $request_id
        ]);
        return;
    }
    
    try {
        // Build SQL query based on segmentation type
        $sql = buildSegmentationQuery($segment_type);
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get column name for grouping
        $key_field = getKeyFieldForSegmentType($segment_type);
        
        $response = [
            'success' => true,
            'data' => [
                'segmentation_type' => $segment_type,
                'total_records' => count($results),
                'limit' => $limit,
                'offset' => $offset,
                'segments' => array_slice($results, $offset, $limit)
            ],
            'metadata' => [
                'request_id' => $request_id,
                'timestamp' => date('c'),
                'api_version' => API_VERSION,
                'response_time_ms' => 0
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database Error',
            'message' => $e->getMessage(),
            'request_id' => $request_id
        ]);
    }
}

function handleListClusters($query_params, $request_id, $pdo) {
    try {
        $sql = "SELECT * FROM cluster_metadata ORDER BY cluster_id";
        $stmt = $pdo->query($sql);
        $clusters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'data' => [
                'total_clusters' => count($clusters),
                'clusters' => $clusters
            ],
            'metadata' => [
                'request_id' => $request_id,
                'timestamp' => date('c'),
                'api_version' => API_VERSION
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database Error',
            'message' => $e->getMessage(),
            'request_id' => $request_id
        ]);
    }
}

function handleRunClustering($request_body, $request_id) {
    $n_clusters = $request_body['n_clusters'] ?? 5;
    $algorithm = $request_body['algorithm'] ?? 'kmeans';
    
    if ($n_clusters < 2 || $n_clusters > 20) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bad Request',
            'message' => 'n_clusters must be between 2 and 20',
            'request_id' => $request_id
        ]);
        return;
    }
    
    // Mock job creation
    $job_id = 'job_clust_' . date('YmdH_i_s');
    
    http_response_code(202);
    echo json_encode([
        'success' => true,
        'data' => [
            'job_id' => $job_id,
            'status' => 'processing',
            'message' => 'Clustering job queued successfully',
            'estimated_time_seconds' => 120
        ],
        'metadata' => [
            'request_id' => $request_id,
            'timestamp' => date('c'),
            'api_version' => API_VERSION
        ]
    ]);
}

function handleGetCustomerSegment($customer_id, $query_params, $request_id, $pdo) {
    try {
        $sql = "SELECT * FROM customers WHERE customer_id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Not Found',
                'message' => "Customer with ID $customer_id not found",
                'request_id' => $request_id
            ]);
            return;
        }
        
        $response = [
            'success' => true,
            'data' => [
                'customer_id' => $customer['customer_id'],
                'name' => $customer['name'] ?? 'N/A',
                'email' => $customer['email'] ?? 'N/A',
                'segments' => [
                    'gender' => $customer['gender'] ?? 'N/A',
                    'region' => $customer['region'] ?? 'N/A',
                    'age_group' => $customer['age_group'] ?? 'N/A',
                    'income_bracket' => $customer['income_bracket'] ?? 'N/A',
                    'purchase_tier' => $customer['purchase_tier'] ?? 'N/A',
                    'clv_tier' => $customer['clv_tier'] ?? 'N/A',
                    'calculated_clv' => $customer['calculated_clv'] ?? 0
                ]
            ],
            'metadata' => [
                'request_id' => $request_id,
                'timestamp' => date('c'),
                'api_version' => API_VERSION
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database Error',
            'message' => $e->getMessage(),
            'request_id' => $request_id
        ]);
    }
}

function handleGetInsights($insight_type, $request_id, $pdo) {
    $valid_types = ['gender', 'region', 'age_group', 'income_bracket', 'cluster', 'purchase_tier', 'clv'];
    
    if (!in_array($insight_type, $valid_types)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bad Request',
            'message' => "Invalid insight type: $insight_type",
            'request_id' => $request_id
        ]);
        return;
    }
    
    // Mock insights (in production, generate from data)
    $insights = [
        [
            'title' => ucfirst($insight_type) . ' Insight 1',
            'description' => 'This is a sample insight for ' . $insight_type . ' segmentation',
            'severity' => 'medium',
            'recommendation' => 'Take action based on this insight'
        ],
        [
            'title' => ucfirst($insight_type) . ' Insight 2',
            'description' => 'Another important finding from ' . $insight_type . ' analysis',
            'severity' => 'low',
            'recommendation' => 'Monitor this metric'
        ]
    ];
    
    $response = [
        'success' => true,
        'data' => [
            'segmentation_type' => $insight_type,
            'generated_at' => date('c'),
            'insights' => $insights,
            'key_metrics' => [
                'total_segments' => 4,
                'avg_size' => 140,
                'variance' => 0.65
            ]
        ],
        'metadata' => [
            'request_id' => $request_id,
            'timestamp' => date('c'),
            'api_version' => API_VERSION
        ]
    ];
    
    http_response_code(200);
    echo json_encode($response);
}

function handleRequestExport($export_type, $request_body, $request_id) {
    $format = $request_body['format'] ?? 'csv';
    $include_charts = $request_body['include_charts'] ?? true;
    
    $valid_formats = ['csv', 'excel', 'pdf'];
    if (!in_array($format, $valid_formats)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bad Request',
            'message' => "Invalid export format: $format",
            'request_id' => $request_id
        ]);
        return;
    }
    
    $export_id = 'exp_' . date('YmdH_i_s') . '_' . rand(100, 999);
    
    http_response_code(202);
    echo json_encode([
        'success' => true,
        'data' => [
            'export_id' => $export_id,
            'status' => 'processing',
            'format' => $format,
            'estimated_size_mb' => 2.5,
            'download_url' => "/csapp/api/exports/$export_id/download",
            'expires_at' => date('c', strtotime('+24 hours'))
        ],
        'metadata' => [
            'request_id' => $request_id,
            'timestamp' => date('c'),
            'api_version' => API_VERSION
        ]
    ]);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function buildSegmentationQuery($type) {
    switch ($type) {
        case 'gender':
            return "SELECT gender, COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income, 
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount 
                    FROM customers GROUP BY gender";
        
        case 'region':
            return "SELECT region, COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income, 
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount 
                    FROM customers GROUP BY region ORDER BY total_customers DESC";
        
        case 'clv':
            return "SELECT COALESCE(clv_tier, 'Bronze') as clv_tier, COUNT(*) AS total_customers,
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount,
                    ROUND(AVG(purchase_frequency), 1) AS avg_frequency,
                    ROUND(AVG(calculated_clv), 2) AS avg_clv,
                    ROUND(AVG(income), 2) AS avg_income,
                    ROUND(AVG(customer_lifespan_months) / 12, 1) AS avg_lifespan_years
                    FROM customers WHERE clv_tier IS NOT NULL OR (calculated_clv IS NOT NULL AND calculated_clv > 0)
                    GROUP BY clv_tier ORDER BY FIELD(clv_tier, 'Platinum', 'Gold', 'Silver', 'Bronze')";
        
        case 'age_group':
            return "SELECT CASE WHEN age BETWEEN 18 AND 25 THEN '18-25'
                    WHEN age BETWEEN 26 AND 40 THEN '26-40'
                    WHEN age BETWEEN 41 AND 60 THEN '41-60' ELSE '61+' END AS age_group,
                    COUNT(*) AS total_customers, ROUND(AVG(income), 2) AS avg_income,
                    ROUND(AVG(purchase_amount), 2) AS avg_purchase_amount
                    FROM customers GROUP BY age_group ORDER BY age_group";
        
        default:
            return "SELECT COUNT(*) AS total_customers FROM customers";
    }
}

function getKeyFieldForSegmentType($type) {
    $key_map = [
        'gender' => 'gender',
        'region' => 'region',
        'age_group' => 'age_group',
        'income_bracket' => 'income_bracket',
        'cluster' => 'cluster_label',
        'purchase_tier' => 'purchase_tier',
        'clv' => 'clv_tier'
    ];
    return $key_map[$type] ?? $type;
}
?>
