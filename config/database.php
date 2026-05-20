<?php

declare(strict_types=1);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'sumbungan_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
