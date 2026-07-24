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

namespace GlpiPlugin\Behaviors;
use CommonITILActor;
use Session;

class ITILFollowup
{
    /**
     * @param \ITILFollowup $fup
     * @return false|void
     */
    public static function beforeAdd(\ITILFollowup $fup)
    {
        if ($fup->input['itemtype'] !== 'Ticket') {
            return;
        }
        $config = Config::getInstance();
        $ticket = new \Ticket();
        if (!$ticket->getFromDB($fup->input['items_id'])) {
            return;
        }
        if ($config->getField('is_itilfollowupcategory_mandatory')) {
            if (!isset($ticket->fields['itilcategories_id'])
                || $ticket->fields['itilcategories_id'] == 0) {
                $fup->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "You must define a category. it's mandatory",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return false;
            }
        }

        if (!$config->getField('addfup_updatetech')) {
            return;
        }

        if (!Session::haveRight('ticket', UPDATE)) {
            return;
        }



        $current_user_id = Session::getLoginUserID();
        $tickets_id = $ticket->getID();

        // Collect all currently assigned users
        $ticket_user = new \Ticket_User();
        $assigned_users = $ticket_user->find([
            'tickets_id' => $tickets_id,
            'type'       => CommonITILActor::ASSIGN,
        ]);

        // Already assigned as the sole tech — nothing to do
        if (count($assigned_users) === 1
            && (int) reset($assigned_users)['users_id'] === $current_user_id) {
            return;
        }

        // Check whether the current user belongs to any group assigned to the ticket
        $group_ticket = new \Group_Ticket();
        $assigned_groups = $group_ticket->find([
            'tickets_id' => $tickets_id,
            'type'       => CommonITILActor::ASSIGN,
        ]);

        // No assigned tech and no assigned group — nothing to replace
        if (count($assigned_users) === 0 && count($assigned_groups) === 0) {
            return;
        }

        $user_in_assigned_group = false;
        if (count($assigned_groups) > 0) {
            foreach ($assigned_groups as $grp) {
                $group_users = \Group_User::getGroupUsers($grp['groups_id']);
                $group_user_ids = array_column($group_users, 'id');
                if (in_array($current_user_id, $group_user_ids)) {
                    $user_in_assigned_group = true;
                    break;
                }
            }
            // When groups are assigned, only proceed if the user belongs to one of them
            if (!$user_in_assigned_group) {
                return;
            }
        }

        // Remove every currently assigned user (there may be several)
        foreach ($assigned_users as $field) {
            if (!isset($field['users_id'])) {
                continue;
            }
            $to_delete = new \Ticket_User();
            $to_delete->deleteByCriteria([
                'tickets_id' => $tickets_id,
                'users_id'   => $field['users_id'],
                'type'       => CommonITILActor::ASSIGN,
            ]);
        }

        // Assign the current user exactly once
        $new_assign = new \Ticket_User();
        $new_assign->add([
            'tickets_id' => $tickets_id,
            'users_id'   => $current_user_id,
            'type'       => CommonITILActor::ASSIGN,
        ]);
    }
}