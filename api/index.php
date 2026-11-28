<?php
declare(strict_types=1);

// Normalize SCRIPT_NAME so auto-detected redirect URIs use root /index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/../index.php';
