<?php
// Update these if needed for your XAMPP MySQL setup
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cclt_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP MySQL has empty password

// Late fee configuration (per day)
define('LATE_FEE_PER_DAY', 1.00);

// App base paths
// Use APP_BASE for routing within the admin area
// Use ASSET_BASE for shared assets located at the app root
define('APP_BASE', '/cclt/admin');
define('ASSET_BASE', '/cclt');
define('ROOT_BASE', '/cclt');