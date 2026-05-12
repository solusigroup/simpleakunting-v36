<?php
require_once 'app/config.php';
echo "BASEURL: " . BASEURL . "\n";
echo "APPROOT: " . APPROOT . "\n";
echo "SERVER['SCRIPT_NAME']: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "SERVER['HTTP_HOST']: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
