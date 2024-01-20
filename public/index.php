<?php

require __DIR__ . '/../vendor/autoload.php';

use Rdb\Factory\AppFactory;

session_start();

$app = AppFactory::create();
$app->run();
