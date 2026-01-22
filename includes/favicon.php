<?php
/**
 * Favicon Include
 * This file includes all necessary favicon links for the website
 * It automatically calculates the correct relative path based on the current file location
 */

// Calculate the relative path to the root directory
$currentDir = dirname($_SERVER['SCRIPT_FILENAME']);
$rootDir = dirname(__DIR__); // Go up one level from includes/
$relativePath = '';

// Calculate how many levels deep we are
$currentParts = explode(DIRECTORY_SEPARATOR, $currentDir);
$rootParts = explode(DIRECTORY_SEPARATOR, $rootDir);

// Find the common path
$depth = count($currentParts) - count($rootParts);

// Build the relative path
for ($i = 0; $i < $depth; $i++) {
    $relativePath .= '../';
}

// If we're at the same level, use current directory
if ($relativePath === '') {
    $relativePath = './';
}
?>
<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="<?php echo $relativePath; ?>assets/favicon/favicon.ico">
<link rel="icon" type="image/svg+xml" href="<?php echo $relativePath; ?>assets/favicon/favicon.svg">
<link rel="icon" type="image/png" sizes="96x96" href="<?php echo $relativePath; ?>assets/favicon/favicon-96x96.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $relativePath; ?>assets/favicon/apple-touch-icon.png">
<link rel="manifest" href="<?php echo $relativePath; ?>assets/favicon/site.webmanifest">
