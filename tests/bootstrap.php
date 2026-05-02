<?php

$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';

$loader->addPsr4('GlpiPlugin\\Behaviors\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Behaviors\\Tests\\', dirname(__DIR__) . '/tests/');
