<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// List of possible config file locations to check
$possibleConfigPaths = [
    __DIR__ . '/../../onboarding/config.php',
    __DIR__ . '/../../../onboarding/config.php',
    '/home/wordweav/domains/wh1487294.ispot.cc/public_html/GameDev-G1/onboarding/config.php',
    dirname(dirname(__DIR__)) . '/onboarding/config.php'
];

// Find the config file
$configPath = '';
foreach ($possibleConfigPaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        break;
    }
}

if (empty($configPath)) {
    http_response_code(500);
    die('Could not locate the config file. Tried: ' . implode(', ', $possibleConfigPaths));
}

require_once $configPath;

// Include essence manager
$essenceManagerPath = __DIR__ . '/essence_manager.php';
if (!file_exists($essenceManagerPath)) {
    http_response_code(500);
    die('Could not locate essence manager file');
}
require_once $essenceManagerPath;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['amount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing amount parameter']);
    exit;
}

$user_id = $_SESSION['user_id'];
$amount = intval($data['amount']);

// Initialize essence manager
$essenceManager = new EssenceManager($pdo);

// Update essence
if ($essenceManager->addEssence($user_id, $amount)) {
    echo json_encode(['success' => true, 'amount' => $amount]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update essence']);
}
?>