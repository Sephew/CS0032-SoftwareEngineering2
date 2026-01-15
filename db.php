<?php
// db.php

// -----------------------
// Database configuration
// -----------------------
$host = 'localhost';       // your database host
$dbname = 'customer_segmentation_ph';      // replace with your actual database name
$username = 'root';        // XAMPP default username
$password = '';            // XAMPP default password (empty string)

// -----------------------
// PDO Connection
// -----------------------
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
