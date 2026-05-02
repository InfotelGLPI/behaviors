<?php

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
