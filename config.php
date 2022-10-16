<?php

\bossanova\Common\Dotenv::get('.env');

// Disable all reporting
set_time_limit(0);

ini_set('date.timezone', $_ENV['TIMEZONE']);
ini_set('session.use_cookies', $_ENV['USE_COOKIES']);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);