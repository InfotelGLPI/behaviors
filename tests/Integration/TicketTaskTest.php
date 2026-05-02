<?php

namespace GlpiPlugin\Behaviors\Tests\Integration;

use GlpiPlugin\Behaviors\Config;
use GlpiPlugin\Behaviors\TicketTask;
use Glpi\Tests\DbTestCase;
use Planning;

class TicketTaskTest extends DbTestCase
{
    private array $savedFields = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->savedFields = Config::getInstance()->fields;
    }

    public function tearDown(): void
    {
        Config::getInstance()->fields = $this->savedFields;
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        parent::tearDown();
    }

    private function enableBehavior(string $field, int $value = 1): void
    {
        Config::getInstance()->fields[$field] = $value;
    }

    // ── Catégorie de tâche obligatoire (beforeAdd) ────────────────────────────

    public function testBeforeAddBlocksWhenCategoryMandatoryAndMissing(): void
    {
        $this->login();
        $this->enableBehavior('is_tickettaskcategory_mandatory');

        $task = new \TicketTask();
        $task->input = [
            'tickets_id'        => 1,
            'content'           => 'Tâche sans catégorie',
            'taskcategories_id' => 0,
        ];

        TicketTask::beforeAdd($task);

        $this->assertFalse($task->input);
    }

    public function testBeforeAddAllowsWhenCategoryProvidedAndMandatory(): void
    {
        $this->login();
        $this->enableBehavior('is_tickettaskcategory_mandatory');

        $category = $this->createItem(\TaskCategory::class, [
            'name'         => 'Catégorie test',
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $task = new \TicketTask();
        $task->input = [
            'tickets_id'        => 1,
            'content'           => 'Tâche avec catégorie',
            'taskcategories_id' => $category->getID(),
        ];

        TicketTask::beforeAdd($task);

        $this->assertIsArray($task->input);
    }

    public function testBeforeAddAllowsWhenCategoryNotMandatory(): void
    {
        $this->login();
        // is_tickettaskcategory_mandatory = 0 (défaut)

        $task = new \TicketTask();
        $task->input = [
            'tickets_id'        => 1,
            'content'           => 'Tâche sans catégorie',
            'taskcategories_id' => 0,
        ];

        TicketTask::beforeAdd($task);

        $this->assertIsArray($task->input);
    }

    // ── Changement de statut bloqué sur ticket résolu (beforeUpdate) ──────────

    public function testBeforeUpdateBlocksStateChangeWhenTicketSolved(): void
    {
        $this->login();
        $this->enableBehavior('is_tickettasktodo');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket résolu',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        // Résoudre le ticket directement via update champ status
        global $DB;
        $DB->update('glpi_tickets', ['status' => \Ticket::SOLVED], ['id' => $ticket->getID()]);

        $task = new \TicketTask();
        $task->fields = [
            'tickets_id'        => $ticket->getID(),
            'taskcategories_id' => 0,
            'state'             => Planning::TODO,
        ];
        $task->input = [
            'state' => Planning::DONE,
        ];

        TicketTask::beforeUpdate($task);

        $this->assertArrayNotHasKey('state', $task->input);
    }

    public function testBeforeUpdateAllowsStateChangeWhenTicketOpen(): void
    {
        $this->login();
        $this->enableBehavior('is_tickettasktodo');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket ouvert',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $task = new \TicketTask();
        $task->fields = [
            'tickets_id'        => $ticket->getID(),
            'taskcategories_id' => 0,
            'state'             => Planning::TODO,
        ];
        $task->input = [
            'state' => Planning::DONE,
        ];

        TicketTask::beforeUpdate($task);

        $this->assertArrayHasKey('state', $task->input);
        $this->assertSame(Planning::DONE, $task->input['state']);
    }
}
