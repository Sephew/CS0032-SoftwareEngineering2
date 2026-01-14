<?php
use PHPUnit\Framework\TestCase;

// Adjust path to your run_clustering.php
require_once __DIR__ . '/../run_clustering.php';

class test_run extends TestCase {
    private $kmeans;

    protected function setUp(): void {
        $this->kmeans = new KMeansClustering();
    }

    /**
     * Helper to access the private euclideanDistance method
     */
    private function getDistance($p1, $p2) {
        $reflection = new ReflectionClass(get_class($this->kmeans));
        $method = $reflection->getMethod('euclideanDistance');
        $method->setAccessible(true);
        return $method->invokeArgs($this->kmeans, [$p1, $p2]);
    }

    // =================================================================
    // EUCLIDEAN DISTANCE TEST CASES
    // =================================================================

    /**
     * Test 1: Pythagorean Theorem (3-4-5 Triangle)
     * Verifies that sqrt(3^2 + 4^2) = 5
     */
    public function testEuclideanPythagoreanStandard() {
        $p1 = ['age' => 0, 'income' => 0, 'purchase' => 0];
        $p2 = ['age' => 3, 'income' => 4, 'purchase' => 0];

        $dist = $this->getDistance($p1, $p2);
        
        $this->assertEquals(5, $dist, "Geometry error: 3-4-5 triangle should yield distance 5.");
    }

    /**
     * Test 2: Identity Property (Distance to Self)
     * Verifies that d(x, x) = 0
     */
    public function testEuclideanIdentity() {
        $p1 = ['age' => 45, 'income' => 75000, 'purchase' => 2500];

        $dist = $this->getDistance($p1, $p1);

        $this->assertEquals(0, $dist, "Identity error: Distance to self must be exactly 0.");
    }

    /**
     * Test 3: Absolute Displacement (Negative Coordinates)
     * Verifies that distance is calculated correctly across axes
     */
    public function testEuclideanNegativeCoordinates() {
        $p1 = ['age' => -5, 'income' => 0, 'purchase' => 0];
        $p2 = ['age' => 5,  'income' => 0, 'purchase' => 0];

        $dist = $this->getDistance($p1, $p2);

        // Distance between -5 and 5 is 10
        $this->assertEquals(10, $dist, "Calculation error: Distance between -5 and 5 should be 10.");
    }
}