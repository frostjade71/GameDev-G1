<?php
/**
 * Dynamic Greeting Function
 * Returns appropriate greeting based on current time of day in Philippine Time Zone
 */

function getGreeting() {
    // Set timezone to Philippine Time (Asia/Manila)
    date_default_timezone_set('Asia/Manila');
    
    $currentHour = (int)date('H');
    
    if ($currentHour >= 5 && $currentHour < 12) {
        return "Good Morning";
    } elseif ($currentHour >= 12 && $currentHour < 17) {
        return "Good Afternoon";
    } elseif ($currentHour >= 17 && $currentHour < 21) {
        return "Good Evening";
    } else {
        return "Good Night";
    }
}
?>
