<?php
header('Content-Type: text/plain');
echo "DATABASE_URL: " . ($_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? 'NOT SET') . "\n";
echo "APP_ENV: " . ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'NOT SET') . "\n";
echo "APP_DEBUG: " . ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'NOT SET') . "\n";
