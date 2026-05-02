<?php

namespace GlpiPlugin\Behaviors\Tests\Integration;

use GlpiPlugin\Behaviors\Config;
use GlpiPlugin\Behaviors\ITILSolution;
use Glpi\Tests\DbTestCase;

class ITILSolutionTest extends DbTestCase
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

    private function makeSolution(\Ticket $ticket, array $extra = []): \ITILSolution
    {
        $soluce = new \ITILSolution();
        $soluce->input = array_merge([
            'itemtype'         => 'Ticket',
            'items_id'         => $ticket->getID(),
            'solutiontypes_id' => 0,
            'content'          => 'Solution description',
            'status'           => \CommonITILObject::SOLVED,
        ], $extra);
        return $soluce;
    }

    // ── Type de solution obligatoire ──────────────────────────────────────────

    public function testBeforeAddBlocksWhenSolutionTypeMandatoryAndMissing(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketsolutiontype_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket sans type solution',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $soluce = $this->makeSolution($ticket, ['solutiontypes_id' => 0]);
        ITILSolution::beforeAdd($soluce);

        $this->assertFalse($soluce->input);
    }

    public function testBeforeAddAllowsWhenSolutionTypeProvidedAndMandatory(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketsolutiontype_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket avec type solution',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $solutionType = $this->createItem(\SolutionType::class, [
            'name'        => 'Résolu',
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);

        $soluce = $this->makeSolution($ticket, ['solutiontypes_id' => $solutionType->getID()]);
        ITILSolution::beforeAdd($soluce);

        $this->assertIsArray($soluce->input);
    }

    // ── Description de solution obligatoire ──────────────────────────────────

    public function testBeforeAddBlocksWhenDescriptionMandatoryAndEmpty(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketsolution_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket sans description solution',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $soluce = $this->makeSolution($ticket, ['content' => '']);
        ITILSolution::beforeAdd($soluce);

        $this->assertFalse($soluce->input);
    }

    public function testBeforeAddAllowsWhenDescriptionProvidedAndMandatory(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketsolution_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket avec description',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $soluce = $this->makeSolution($ticket, ['content' => 'Ma solution détaillée']);
        ITILSolution::beforeAdd($soluce);

        $this->assertIsArray($soluce->input);
    }

    // ── Durée obligatoire ────────────────────────────────────────────────────

    public function testBeforeAddBlocksWhenRealTimeMandatoryAndZero(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketrealtime_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket sans durée',
            'content'     => 'Contenu',
            'entities_id' => 0,
            'actiontime'  => 0,
        ]);

        $soluce = $this->makeSolution($ticket);
        ITILSolution::beforeAdd($soluce);

        $this->assertFalse($soluce->input);
    }

    public function testBeforeAddAllowsWhenRealTimeFilledAndMandatory(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketrealtime_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket avec durée',
            'content'     => 'Contenu',
            'entities_id' => 0,
            'actiontime'  => 3600,
        ]);

        $soluce = $this->makeSolution($ticket);
        ITILSolution::beforeAdd($soluce);

        $this->assertIsArray($soluce->input);
    }

    // ── Catégorie obligatoire ─────────────────────────────────────────────────

    public function testBeforeAddBlocksWhenCategoryMandatoryAndMissing(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketcategory_mandatory');

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket sans catégorie',
            'content'          => 'Contenu',
            'entities_id'      => 0,
            'itilcategories_id' => 0,
        ]);

        $soluce = $this->makeSolution($ticket);
        ITILSolution::beforeAdd($soluce);

        $this->assertFalse($soluce->input);
    }

    // ── Tâches à faire bloquent la résolution ─────────────────────────────────

    public function testBeforeAddBlocksWhenPendingTasksExist(): void
    {
        $this->login();
        $this->enableBehavior('is_tickettasktodo');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket avec tâche en attente',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $this->createItem(\TicketTask::class, [
            'tickets_id' => $ticket->getID(),
            'content'    => 'Tâche à faire',
            'state'      => \Planning::TODO,
        ]);

        $soluce = $this->makeSolution($ticket);
        ITILSolution::beforeAdd($soluce);

        $this->assertFalse($soluce->input);
    }

    public function testBeforeAddAllowsWhenAllTasksDone(): void
    {
        $this->login();
        $this->enableBehavior('is_tickettasktodo');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket avec tâche terminée',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $this->createItem(\TicketTask::class, [
            'tickets_id' => $ticket->getID(),
            'content'    => 'Tâche terminée',
            'state'      => \Planning::DONE,
        ]);

        $soluce = $this->makeSolution($ticket);
        ITILSolution::beforeAdd($soluce);

        $this->assertIsArray($soluce->input);
    }

    // ── Aucun comportement si désactivé ───────────────────────────────────────

    public function testBeforeAddDoesNothingWhenBehaviorsDisabled(): void
    {
        $this->login();
        // Tous les comportements restent à 0 (défaut)

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket sans contrainte',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $soluce = $this->makeSolution($ticket, [
            'solutiontypes_id' => 0,
            'content'          => '',
        ]);
        ITILSolution::beforeAdd($soluce);

        $this->assertIsArray($soluce->input);
    }

    // ── Problème : type de solution obligatoire ───────────────────────────────

    public function testBeforeAddBlocksProblemWhenSolutionTypeMandatoryAndMissing(): void
    {
        $this->login();
        $this->enableBehavior('is_problemsolutiontype_mandatory');

        $problem = $this->createItem(\Problem::class, [
            'name'        => 'Problème sans type solution',
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);

        $soluce        = new \ITILSolution();
        $soluce->input = [
            'itemtype'         => 'Problem',
            'items_id'         => $problem->getID(),
            'solutiontypes_id' => 0,
            'content'          => 'Solution',
            'status'           => \CommonITILObject::SOLVED,
        ];
        ITILSolution::beforeAdd($soluce);

        $this->assertFalse($soluce->input);
    }
}
