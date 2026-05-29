<?php
session_start();
echo "<h1>Session Debug</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<a href='/customer/dashboard.php'>Go to Dashboard</a>";
?>
