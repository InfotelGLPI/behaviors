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

use GlpiPlugin\Behaviors\Ticket;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    public function testRemoveDuplicatesKeepsUniqueActors(): void
    {
        $actors = [
            ['itemtype' => 'User',  'items_id' => 1],
            ['itemtype' => 'Group', 'items_id' => 2],
            ['itemtype' => 'User',  'items_id' => 3],
        ];

        $result = Ticket::removeDuplicates($actors);

        $this->assertCount(3, $result);
    }

    public function testRemoveDuplicatesRemovesDuplicatesByCompositeKey(): void
    {
        $actors = [
            ['itemtype' => 'User', 'items_id' => 5, 'use_notification' => '1'],
            ['itemtype' => 'User', 'items_id' => 5, 'use_notification' => '0'],
            ['itemtype' => 'User', 'items_id' => 6],
        ];

        $result = Ticket::removeDuplicates($actors);

        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['use_notification']);
    }

    public function testRemoveDuplicatesDifferentItemtypeSameIdAreKept(): void
    {
        $actors = [
            ['itemtype' => 'User',  'items_id' => 1],
            ['itemtype' => 'Group', 'items_id' => 1],
        ];

        $result = Ticket::removeDuplicates($actors);

        $this->assertCount(2, $result);
    }

    public function testRemoveDuplicatesWithEmptyArrayReturnsEmpty(): void
    {
        $result = Ticket::removeDuplicates([]);

        $this->assertSame([], $result);
    }

    public function testRemoveDuplicatesWithNonArrayReturnsNull(): void
    {
        $result = Ticket::removeDuplicates('not-an-array');

        $this->assertNull($result);
    }

    public function testRemoveDuplicatesPreservesFirstOccurrence(): void
    {
        $actors = [
            ['itemtype' => 'User', 'items_id' => 10, 'flag' => 'first'],
            ['itemtype' => 'User', 'items_id' => 10, 'flag' => 'second'],
        ];

        $result = Ticket::removeDuplicates($actors);

        $this->assertCount(1, $result);
        $this->assertSame('first', $result[0]['flag']);
    }
}
