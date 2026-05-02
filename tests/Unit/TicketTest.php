<?php

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
