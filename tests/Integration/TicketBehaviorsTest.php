<?php

namespace GlpiPlugin\Behaviors\Tests\Integration;

use CommonITILActor;
use GlpiPlugin\Behaviors\Config;
use GlpiPlugin\Behaviors\Ticket as BehaviorsTicket;
use Glpi\Tests\DbTestCase;

class TicketBehaviorsTest extends DbTestCase
{
    private array $savedFields = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedFields = Config::getInstance()->fields;
    }

    protected function tearDown(): void
    {
        Config::getInstance()->fields = $this->savedFields;
        parent::tearDown();
    }

    private function enableBehavior(string $field, int $value = 1): void
    {
        Config::getInstance()->fields[$field] = $value;
    }

    // ── single_tech_mode ─────────────────────────────────────────────────────

    public function testSingleTechModeRemovesPreviousTechOnNewAssign(): void
    {
        $this->login();
        $this->enableBehavior('single_tech_mode', 1);

        $tech1 = $this->createItem(\User::class, [
            'name'         => 'tech.one',
            'entities_id'  => 0,
            'profiles_id'  => 4,
            '_profiles_id' => 4,
        ]);
        $tech2 = $this->createItem(\User::class, [
            'name'         => 'tech.two',
            'entities_id'  => 0,
            'profiles_id'  => 4,
            '_profiles_id' => 4,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'                => 'Ticket single tech',
            'content'             => 'Contenu',
            'entities_id'         => 0,
            '_users_id_assign'    => $tech1->getID(),
        ]);

        // Assigner un deuxième tech — single_tech_mode doit retirer le premier
        $ticketUser = new \Ticket_User();
        $ticketUser->add([
            'tickets_id' => $ticket->getID(),
            'users_id'   => $tech2->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $assigned = $ticketUser->find([
            'tickets_id' => $ticket->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $this->assertCount(1, $assigned);
        $this->assertSame($tech2->getID(), (int) reset($assigned)['users_id']);
    }

    // ── use_requester_user_group (via beforeAdd) ──────────────────────────────

    public function testBeforeAddAddsRequesterGroupWhenEnabled(): void
    {
        $this->login();
        $this->enableBehavior('use_requester_user_group', 1);

        $group = $this->createItem(\Group::class, [
            'name'         => 'Groupe requérant',
            'entities_id'  => 0,
            'is_requester' => 1,
        ]);

        $user = $this->createItem(\User::class, [
            'name'        => 'requester.user',
            'entities_id' => 0,
        ]);

        // Ajouter l'utilisateur au groupe
        $groupUser = new \Group_User();
        $groupUser->add([
            'groups_id' => $group->getID(),
            'users_id'  => $user->getID(),
        ]);

        $ticket = new \Ticket();
        $ticket->input = [
            'name'             => 'Ticket groupe requérant',
            'content'          => 'Contenu',
            'entities_id'      => 0,
            '_users_id_requester' => $user->getID(),
        ];

        BehaviorsTicket::beforeAdd($ticket);

        // Le groupe doit être présent dans les acteurs requérants
        $groupIds = array_column(
            $ticket->input['_actors']['requester'] ?? [],
            'items_id',
            'itemtype'
        );

        $this->assertArrayHasKey('Group', $groupIds);
        $this->assertSame($group->getID(), (int) $groupIds['Group']);
    }

    // ── ticketsolved_updatetech ───────────────────────────────────────────────

    public function testBeforeAddAssignsConnectedTechWhenSolvingWithUpdateTechEnabled(): void
    {
        $this->login();
        $this->enableBehavior('ticketsolved_updatetech');

        $ticket = new \Ticket();
        $ticket->input = [
            'name'              => 'Ticket résolution auto-tech',
            'content'           => 'Contenu',
            'entities_id'       => 0,
            'status'            => \Ticket::SOLVED,
            '_users_id_assign'  => 0,
        ];

        BehaviorsTicket::beforeAdd($ticket);

        $this->assertSame(
            \Session::getLoginUserID(),
            (int) $ticket->input['_users_id_assign']
        );
    }

    // ── Catégorie obligatoire à l'assignation ─────────────────────────────────

    public function testBeforeUpdateBlocksAssignWithoutCategoryWhenMandatory(): void
    {
        $this->login();
        $this->enableBehavior('is_ticketcategory_mandatory_on_assign');

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket sans catégorie',
            'content'          => 'Contenu',
            'entities_id'      => 0,
            'itilcategories_id' => 0,
        ]);

        $tech = $this->createItem(\User::class, [
            'name'        => 'tech.assign',
            'entities_id' => 0,
        ]);

        $ticket->input = [
            'id'                  => $ticket->getID(),
            '_users_id_assign'    => $tech->getID(),
            'itilcategories_id'   => 0,
        ];

        BehaviorsTicket::beforeUpdate($ticket);

        $this->assertFalse($ticket->input);
    }

    // ── use_requester_user_group : vérification DB après création ─────────────

    public function testRequesterGroupAssociatedToTicketAfterCreation(): void
    {
        $this->login();
        $this->enableBehavior('use_requester_user_group', 1);

        $group = $this->createItem(\Group::class, [
            'name'         => 'Groupe demandeur auto',
            'entities_id'  => 0,
            'is_requester' => 1,
            'is_recursive' => 1,
        ]);

        $user = $this->createItem(\User::class, [
            'name'        => 'demandeur.groupe.auto',
            'entities_id' => 0,
        ]);

        $this->createItem(\Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => $user->getID(),
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'                => 'Ticket groupe demandeur auto',
            'content'             => 'Contenu',
            'entities_id'         => 0,
            '_users_id_requester' => $user->getID(),
        ]);

        $requesterGroups = $ticket->getGroups(CommonITILActor::REQUESTER);
        $groupIds = array_column($requesterGroups, 'groups_id');

        $this->assertContains(
            $group->getID(),
            $groupIds,
            "Le groupe demandeur de l'utilisateur doit être associé au ticket."
        );
    }

    // ── use_assign_user_group : vérification DB après création ───────────────

    public function testAssignGroupAssociatedToTicketAfterCreation(): void
    {
        $this->login();
        $this->enableBehavior('use_assign_user_group', 1);

        $group = $this->createItem(\Group::class, [
            'name'         => 'Groupe technicien auto',
            'entities_id'  => 0,
            'is_assign'    => 1,
            'is_recursive' => 1,
        ]);

        $tech = $this->createItem(\User::class, [
            'name'         => 'tech.groupe.auto',
            'entities_id'  => 0,
            'profiles_id'  => 4,
            '_profiles_id' => 4,
        ]);

        $this->createItem(\Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => $tech->getID(),
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket groupe technicien auto',
            'content'          => 'Contenu',
            'entities_id'      => 0,
            '_users_id_assign' => $tech->getID(),
        ]);

        $assignGroups = $ticket->getGroups(CommonITILActor::ASSIGN);
        $groupIds = array_column($assignGroups, 'groups_id');

        $this->assertContains(
            $group->getID(),
            $groupIds,
            "Le groupe du technicien doit être associé au ticket."
        );
    }
}
