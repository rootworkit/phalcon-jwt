<?php

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new \RuntimeException(
        'Unable to locate autoloader. ' .
        'Install dependencies from the project root directory to run test suite: `composer install`.'
    );
}

require __DIR__ . '/../vendor/autoload.php';
