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

namespace GlpiPlugin\Behaviors\Tests\Integration;

use CommonITILActor;
use GlpiPlugin\Behaviors\Config;
use GlpiPlugin\Behaviors\ITILFollowup as BehaviorsITILFollowup;
use Glpi\Tests\DbTestCase;

class ITILFollowupTest extends DbTestCase
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

    /**
     * Build an ITILFollowup input array (does not persist to DB).
     */
    private function makeFupInput(\Ticket $ticket): array
    {
        return [
            'itemtype' => 'Ticket',
            'items_id' => $ticket->getID(),
            'content'  => 'Test followup',
        ];
    }

    /**
     * Return the list of assigned user IDs on a ticket.
     *
     * @return int[]
     */
    private function getAssignedUserIds(\Ticket $ticket): array
    {
        $ticket_user = new \Ticket_User();
        $rows = $ticket_user->find([
            'tickets_id' => $ticket->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);
        return array_map(static fn($r) => (int) $r['users_id'], array_values($rows));
    }

    // ── Option désactivée ────────────────────────────────────────────────────

    public function testDoesNothingWhenBehaviorDisabled(): void
    {
        $this->login();
        // addfup_updatetech is 0 by default — do NOT enable it

        $other_tech = $this->createItem(\User::class, [
            'name'        => 'fup.tech.disabled',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket behavior disabled',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $other_tech->getID(),
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertSame([$other_tech->getID()], $assigned, 'Assigned user must not change when behavior is disabled.');
    }

    // ── itemtype non-Ticket ──────────────────────────────────────────────────

    public function testDoesNothingForNonTicketItemtype(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $other_tech = $this->createItem(\User::class, [
            'name'        => 'fup.tech.change',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket non-itemtype check',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $other_tech->getID(),
        ]);

        $fup = new \ITILFollowup();
        $fup->input = [
            'itemtype' => 'Change',
            'items_id' => $ticket->getID(),
            'content'  => 'Test followup',
        ];

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertSame([$other_tech->getID()], $assigned, 'Non-Ticket itemtype must not trigger reassignment.');
    }

    // ── Utilisateur courant déjà seul assigné ────────────────────────────────

    public function testDoesNothingWhenCurrentUserAlreadyAssigned(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $current_user_id = \Session::getLoginUserID();

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket current user already assigned',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $current_user_id,
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertSame([$current_user_id], $assigned, 'Current user already assigned — nothing should change.');
    }

    // ── Remplacement sans groupe assigné ────────────────────────────────────

    public function testReplacesAssignedTechWithCurrentUserWhenNoGroupAssigned(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $other_tech = $this->createItem(\User::class, [
            'name'        => 'fup.other.tech.nogroup',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket replace tech no group',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $other_tech->getID(),
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertCount(1, $assigned, 'Exactly one technician must be assigned after the followup.');
        $this->assertSame(\Session::getLoginUserID(), $assigned[0], 'The current user must replace the previous tech.');
    }

    // ── Avec groupe assigné : utilisateur dans le groupe ────────────────────

    public function testReplacesAssignedTechWhenCurrentUserBelongsToAssignedGroup(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $current_user_id = \Session::getLoginUserID();

        $group = $this->createItem(\Group::class, [
            'name'        => 'Assigned group fup',
            'entities_id' => 0,
            'is_assign'   => 1,
        ]);

        // Put the current (logged-in) user in that group
        $this->createItem(\Group_User::class, [
            'groups_id' => $group->getID(),
            'users_id'  => $current_user_id,
        ]);

        $other_tech = $this->createItem(\User::class, [
            'name'        => 'fup.other.tech.ingroup',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket replace tech in group',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $other_tech->getID(),
        ]);

        // Assign the group to the ticket
        $this->createItem(\Group_Ticket::class, [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertCount(1, $assigned, 'Exactly one technician must be assigned after the followup.');
        $this->assertSame($current_user_id, $assigned[0], 'The current user must replace the previous tech.');
    }

    // ── Avec groupe assigné : utilisateur hors du groupe ────────────────────

    public function testDoesNotReplaceWhenCurrentUserNotInAssignedGroup(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $current_user_id = \Session::getLoginUserID();

        $group = $this->createItem(\Group::class, [
            'name'        => 'Assigned group fup outsider',
            'entities_id' => 0,
            'is_assign'   => 1,
        ]);

        // Current user is NOT added to the group

        $other_tech = $this->createItem(\User::class, [
            'name'        => 'fup.other.tech.outgroup',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket no replace tech outside group',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $other_tech->getID(),
        ]);

        $this->createItem(\Group_Ticket::class, [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertSame([$other_tech->getID()], $assigned, 'Current user is not in the assigned group — tech must not change.');
    }

    // ── Plusieurs groupes : utilisateur dans l'un d'eux ─────────────────────

    public function testReplacesWhenCurrentUserInOneOfMultipleAssignedGroups(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $current_user_id = \Session::getLoginUserID();

        $group_a = $this->createItem(\Group::class, [
            'name'        => 'Assigned group A multi',
            'entities_id' => 0,
            'is_assign'   => 1,
        ]);
        $group_b = $this->createItem(\Group::class, [
            'name'        => 'Assigned group B multi',
            'entities_id' => 0,
            'is_assign'   => 1,
        ]);

        // Current user belongs only to group B
        $this->createItem(\Group_User::class, [
            'groups_id' => $group_b->getID(),
            'users_id'  => $current_user_id,
        ]);

        $other_tech = $this->createItem(\User::class, [
            'name'        => 'fup.other.tech.multigroup',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket multi-group replace',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $other_tech->getID(),
        ]);

        $this->createItem(\Group_Ticket::class, [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_a->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);
        $this->createItem(\Group_Ticket::class, [
            'tickets_id' => $ticket->getID(),
            'groups_id'  => $group_b->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertCount(1, $assigned, 'Exactly one technician must be assigned after the followup.');
        $this->assertSame($current_user_id, $assigned[0], 'User in one of the assigned groups must replace the tech.');
    }

    // ── Plusieurs techs assignés : aucun double ajout ────────────────────────

    public function testReplacesMultipleAssignedTechsWithCurrentUserExactlyOnce(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $current_user_id = \Session::getLoginUserID();

        $tech1 = $this->createItem(\User::class, [
            'name'        => 'fup.multi.tech1',
            'entities_id' => 0,
        ]);
        $tech2 = $this->createItem(\User::class, [
            'name'        => 'fup.multi.tech2',
            'entities_id' => 0,
        ]);

        $ticket = $this->createItem(\Ticket::class, [
            'name'             => 'Ticket multiple techs',
            'content'          => 'Content',
            'entities_id'      => 0,
            '_users_id_assign' => $tech1->getID(),
        ]);

        // Manually add a second assigned technician
        $this->createItem(\Ticket_User::class, [
            'tickets_id' => $ticket->getID(),
            'users_id'   => $tech2->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertCount(1, $assigned, 'Current user must be assigned exactly once even when multiple techs were assigned.');
        $this->assertSame($current_user_id, $assigned[0]);
    }

    // ── Aucun technicien assigné ─────────────────────────────────────────────

    public function testDoesNothingWhenNoTechAssigned(): void
    {
        $this->login();
        $this->enableBehavior('addfup_updatetech');

        $ticket = $this->createItem(\Ticket::class, [
            'name'        => 'Ticket no tech assigned',
            'content'     => 'Content',
            'entities_id' => 0,
        ]);

        $fup = new \ITILFollowup();
        $fup->input = $this->makeFupInput($ticket);

        BehaviorsITILFollowup::beforeAdd($fup);

        $assigned = $this->getAssignedUserIds($ticket);
        $this->assertEmpty($assigned, 'No tech assigned — nothing should happen.');
    }
}
