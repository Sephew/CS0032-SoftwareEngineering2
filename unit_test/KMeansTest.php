<?php
use PHPUnit\Framework\TestCase;

// Adjust this path to your actual class file location
require_once __DIR__ . '/../run_clustering.php';

class KMeansTest extends TestCase {
    private $kmeans;

    protected function setUp(): void {
        $this->kmeans = new KMeansClustering();
    }

    // --- SECTION 1: normalizeData() TESTS ---

    public function testNormalizeNormalData() {
        // Input [10, 20, 30] -> Mean 20, StdDev ~8.16
        $data = [
            ['val' => 10], ['val' => 20], ['val' => 30]
        ];
        $result = $this->kmeans->normalizeData($data);
        
        // Assert roughly [-1.22, 0, 1.22]
        $this->assertEqualsWithDelta(-1.22, $result[0]['val'], 0.01);
        $this->assertEquals(0, $result[1]['val']);
        $this->assertEqualsWithDelta(1.22, $result[2]['val'], 0.01);
    }

    public function testNormalizeZeroStandardDeviation() {
        // Input [50, 50] -> Variance is 0. Should return 0, not crash.
        $data = [
            ['val' => 50], ['val' => 50]
        ];
        $result = $this->kmeans->normalizeData($data);
        
        $this->assertEquals(0, $result[0]['val']);
        $this->assertEquals(0, $result[1]['val']);
    }

    public function testNormalizeNegativeValues() {
        // Input [-10, 0, 10]
        $data = [
            ['val' => -10], ['val' => 0], ['val' => 10]
        ];
        $result = $this->kmeans->normalizeData($data);
        
        $this->assertLessThan(0, $result[0]['val']); // Should be negative Z-score
        $this->assertGreaterThan(0, $result[2]['val']); // Should be positive Z-score
    }

    public function testNormalizeEmptyArray() {
        $result = $this->kmeans->normalizeData([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // --- SECTION 2: euclideanDistance() TESTS ---

    public function testEuclideanDistanceTriangle() {
        $reflector = new ReflectionClass($this->kmeans);
        $method = $reflector->getMethod('euclideanDistance');
        $method->setAccessible(true);

        // 3-4-5 Triangle Rule
        $p1 = ['age' => 0, 'income' => 0];
        $p2 = ['age' => 3, 'income' => 4];
        
        $dist = $method->invokeArgs($this->kmeans, [$p1, $p2]);
        $this->assertEquals(5, $dist);
    }
}