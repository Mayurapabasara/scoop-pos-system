<?php

if (! file_exists(__DIR__.'/../.env.testing')) {
    fwrite(STDERR, "The MySQL test profile requires a dedicated .env.testing file. Copy .env.testing.example and configure it first.\n");

    exit(1);
}

require __DIR__.'/../vendor/autoload.php';
