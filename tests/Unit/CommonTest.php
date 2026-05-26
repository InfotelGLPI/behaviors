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

namespace GlpiPlugin\Behaviors\Tests\Unit;

use GlpiPlugin\Behaviors\Common;
use PHPUnit\Framework\TestCase;

class CommonTest extends TestCase
{
    private array $originalCloneTypes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCloneTypes = Common::getCloneTypes();
    }

    protected function tearDown(): void
    {
        // Restaure les types d'origine pour ne pas polluer les autres tests
        foreach (array_keys(Common::getCloneTypes()) as $key) {
            if (!isset($this->originalCloneTypes[$key])) {
                unset(Common::$clone_types[$key]);
            }
        }
        parent::tearDown();
    }

    public function testGetCloneTypesReturnsArray(): void
    {
        $types = Common::getCloneTypes();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    public function testGetCloneTypesContainsExpectedTypes(): void
    {
        $types = Common::getCloneTypes();

        $this->assertArrayHasKey('RuleTicket', $types);
        $this->assertArrayHasKey('NotificationTemplate', $types);
        $this->assertArrayHasKey('Profile', $types);
    }

    public function testAddCloneTypeRegistersNewType(): void
    {
        Common::addCloneType('MyPlugin\\CustomType', 'MyPlugin\\CustomManager');

        $types = Common::getCloneTypes();
        $this->assertArrayHasKey('MyPlugin\\CustomType', $types);
        $this->assertSame('MyPlugin\\CustomManager', $types['MyPlugin\\CustomType']);
    }

    public function testAddCloneTypeDoesNotOverwriteExistingEntry(): void
    {
        Common::addCloneType('RuleTicket', 'NewManagerClass');

        $this->assertSame('GlpiPlugin\\Behaviors\\Rule', Common::getCloneTypes()['RuleTicket']);
    }
}
