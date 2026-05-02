<?php

// Enregistrer les namespaces PSR-4 du plugin avant le bootstrap GLPI
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
$loader->addPsr4('GlpiPlugin\\Behaviors\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Behaviors\\Tests\\', dirname(__DIR__) . '/tests/');

// Bootstrap GLPI complet : initialise la DB, le cache et les fixtures de test
// (require_once dans GLPI's bootstrap ne recharge pas l'autoloader déjà actif)
require dirname(__DIR__, 3) . '/tests/bootstrap.php';
