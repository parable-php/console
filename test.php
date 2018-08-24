<?php

use Parable\Console\App;
use Parable\Console\Command\Help;
use Parable\Di\Container;

require 'vendor/autoload.php';

$container = new Container();

$app = $container->get(App::class);

$command = new Help();

$app->setName('<light_blue>Test application</light_blue>');
$app->setDefaultCommand($command);

$app->run();
