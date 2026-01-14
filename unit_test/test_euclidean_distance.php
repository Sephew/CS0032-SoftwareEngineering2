<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../run_clustering.php';

header('Content-Type: text/plain');

// get private method
function getPrivateMethod($object, $methodName) {
    $reflection = new ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    return $method;
}

$kmeans = new KMeansClustering(3);
$euclideanMethod = getPrivateMethod($kmeans, 'euclideanDistance');
$testResults = [];

echo "EUCLIDEAN DISTANCE TESTS\n";
echo "========================\n\n";

// Test 1
$p1 = ['age' => 0, 'income' => 0, 'purchase_amount' => 0];
$p2 = ['age' => 3, 'income' => 4, 'purchase_amount' => 0];
$expected = 5.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 1: 3-4-5 Triangle\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 2
$p1 = ['age' => 25, 'income' => 50000, 'purchase_amount' => 100];
$p2 = $p1;
$expected = 0.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 2: Distance to Self\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 3
$p1 = ['age' => 0, 'income' => 0, 'purchase_amount' => 0];
$p2 = ['age' => 1, 'income' => 0, 'purchase_amount' => 0];
$expected = 1.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 3: Age Axis Only\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 4
$p1 = ['age' => 0, 'income' => 0, 'purchase_amount' => 0];
$p2 = ['age' => 0, 'income' => 1, 'purchase_amount' => 0];
$expected = 1.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 4: Income Axis Only\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 5
$p1 = ['age' => 0, 'income' => 0, 'purchase_amount' => 0];
$p2 = ['age' => 0, 'income' => 0, 'purchase_amount' => 1];
$expected = 1.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 5: Purchase Axis Only\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 6
$p1 = ['age' => -5, 'income' => 0, 'purchase_amount' => 0];
$p2 = ['age' => 5, 'income' => 0, 'purchase_amount' => 0];
$expected = 10.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 6: Negative Coordinates\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 7
$p1 = ['age' => -10, 'income' => -20, 'purchase_amount' => -30];
$p2 = ['age' => -13, 'income' => -24, 'purchase_amount' => -30];
$expected = 5.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 7: All Negative\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 8
$p1 = ['age' => 0, 'income' => 0, 'purchase_amount' => 0];
$p2 = ['age' => 2, 'income' => 3, 'purchase_amount' => 6];
$expected = 7.0;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.0001;
echo "Test 8: 3D Distance\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 9
$p1 = ['age' => 25, 'income' => 30000, 'purchase_amount' => 1000];
$p2 = ['age' => 35, 'income' => 50000, 'purchase_amount' => 2000];
$expected = 20024.99;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.01;
echo "Test 9: Large Numbers\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 10
$p1 = ['age' => 1.5, 'income' => 2.5, 'purchase_amount' => 3.5];
$p2 = ['age' => 4.5, 'income' => 5.5, 'purchase_amount' => 6.5];
$expected = 5.196152;
$actual = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$passed = abs($expected - $actual) < 0.001;
echo "Test 10: Decimal Values\n";
echo "Expected: $expected | Actual: $actual | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 11
$p1 = ['age' => 10, 'income' => 100, 'purchase_amount' => 50];
$p2 = ['age' => 20, 'income' => 200, 'purchase_amount' => 100];
$distAB = $euclideanMethod->invokeArgs($kmeans, [$p1, $p2]);
$distBA = $euclideanMethod->invokeArgs($kmeans, [$p2, $p1]);
$passed = abs($distAB - $distBA) < 0.0001;
echo "Test 11: Symmetry\n";
echo "A to B: $distAB | B to A: $distBA | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// Test 12
$pA = ['age' => 0, 'income' => 0, 'purchase_amount' => 0];
$pB = ['age' => 3, 'income' => 4, 'purchase_amount' => 0];
$pC = ['age' => 6, 'income' => 8, 'purchase_amount' => 0];
$distAB = $euclideanMethod->invokeArgs($kmeans, [$pA, $pB]);
$distBC = $euclideanMethod->invokeArgs($kmeans, [$pB, $pC]);
$distAC = $euclideanMethod->invokeArgs($kmeans, [$pA, $pC]);
$passed = $distAC <= ($distAB + $distBC + 0.0001);
echo "Test 12: Triangle Inequality\n";
echo "AC: $distAC | AB+BC: " . ($distAB + $distBC) . " | " . ($passed ? "PASS" : "FAIL") . "\n\n";
$testResults[] = $passed;

// summary
$passed = count(array_filter($testResults));
$total = count($testResults);
echo "========================\n";
echo "SUMMARY\n";
echo "Total: $total | Passed: $passed | Failed: " . ($total - $passed) . "\n";
echo ($passed === $total ? "ALL TESTS PASSED" : "SOME TESTS FAILED") . "\n";

?>