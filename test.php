<?php
// test.php - Simple test file
echo "<h1>AutoDealer Test Page</h1>";
echo "<p>PHP is working!</p>";

// Check database
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $stmt = $db->query("SELECT 1");
    echo "<p style='color:green'>✅ Database connected!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}

phpinfo();
?>
