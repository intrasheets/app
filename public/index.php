<?php

// Base folder outside public
chdir(__DIR__ . '/..');

if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
}

include 'config.php';

// Translate
bossanova\Translate\Translate::start('en_GB');

// Run application
bossanova\Render\Render::run();
