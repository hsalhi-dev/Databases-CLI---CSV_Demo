#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;
use App\Command\ImportCsvCommand;

$dotenv = new Dotenv();
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv->usePutenv()->load(__DIR__ . '/../.env');
}

$app = new Application('CSV Importer', '1.0.0');

$app->addCommands([
    new ImportCsvCommand(),
]);

$app->run();