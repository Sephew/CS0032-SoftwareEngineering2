<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../run_clustering.php';
require_once 'kmeans.php';

// Mock DB connection since your script requires 'db.php'
// If you don't have db.php ready, comment out 'require_once db.php' in kmeans.php
$kmeans = new KMeansClustering(3);

echo "--- STARTING NORMALIZATION TESTS ---\n\n";

// SCENARIO 1: Normal Data & Negative Values
$data1 = [
    ['customer_id' => 1, 'age' => 10, 'income' => -100, 'purchase_amount' => 50],
    ['customer_id' => 2, 'age' => 20, 'income' => 0,    'purchase_amount' => 100],
    ['customer_id' => 3, 'age' => 30, 'income' => 100,  'purchase_amount' => 150],
];

echo "Test 1: Normal + Negative Values\n";
$result1 = $kmeans->normalizeData($data1);
print_r($result1); 

// SCENARIO 2: Zero Standard Deviation (All values same)
$data2 = [
    ['customer_id' => 4, 'age' => 25, 'income' => 5000, 'purchase_amount' => 200],
    ['customer_id' => 5, 'age' => 25, 'income' => 5000, 'purchase_amount' => 200],
];

echo "\nTest 2: Zero Standard Deviation (Should not crash)\n";
$result2 = $kmeans->normalizeData($data2);
print_r($result2 . "\n");

// SCENARIO 3: Empty Array
echo "\nTest 3: Empty Array\n";
try {
    $result3 = $kmeans->normalizeData([]);
    echo "Success: Handled empty array.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}