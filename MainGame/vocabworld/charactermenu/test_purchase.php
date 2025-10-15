<?php
require_once '../../../onboarding/config.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];

echo "<h2>Test Purchase Functionality</h2>";

// Simulate the purchase request
$test_data = [
    'characterType' => 'amber',
    'price' => 20
];

echo "<h3>Testing Purchase with Data:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// Test the purchase_character.php endpoint
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/purchase_character.php';

echo "<h3>Making request to: $url</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Response (HTTP $http_code):</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Parse response
$result = json_decode($response, true);
if ($result) {
    echo "<h3>Parsed Response:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if (isset($result['success']) && $result['success']) {
        echo "<p>✅ Purchase successful!</p>";
    } else {
        echo "<p>❌ Purchase failed: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p>❌ Could not parse JSON response</p>";
}

echo "<hr>";
echo "<p><a href='debug_database.php'>Check Database</a> | <a href='shop_characters.php'>Go to Shop</a></p>";
?>


