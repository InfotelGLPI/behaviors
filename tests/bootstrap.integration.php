<?php

/*
 * LICENSE
 *
 * This file is part of Behaviors plugin for GLPI.
 *
 * Behaviors is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Behaviors is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   behaviors
 * @author    Infotel, Remi Collet, Nelly Mahu-Lasson
 * @copyright Copyright (c) 2018-2026 Behaviors plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://github.com/InfotelGLPI/behaviors/
 * @link      http://www.glpi-project.org/
 * @since     2010
 --------------------------------------------------------------------------
 */

// Enregistrer les namespaces PSR-4 du plugin avant le bootstrap GLPI
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
$loader->addPsr4('GlpiPlugin\\Behaviors\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Behaviors\\Tests\\', dirname(__DIR__) . '/tests/');

// Bootstrap GLPI complet : initialise la DB, le cache et les fixtures de test
// (require_once dans GLPI's bootstrap ne recharge pas l'autoloader déjà actif)
require dirname(__DIR__, 3) . '/tests/bootstrap.php';
