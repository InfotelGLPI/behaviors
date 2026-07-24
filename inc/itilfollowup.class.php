<?php

/**
 * -------------------------------------------------------------------------
 *
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
 * @copyright Copyright (c) 2018-2025 Behaviors plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://github.com/InfotelGLPI/behaviors/
 * @link      http://www.glpi-project.org/
 * @since     2010
 *
 * --------------------------------------------------------------------------
 */

class PluginBehaviorsITILFollowup
{
    /**
     * @param ITILFollowup $fup
     * @return false|void
     */
    public static function beforeAdd(ITILFollowup $fup)
    {
        $ticket = new Ticket();
        $config = PluginBehaviorsConfig::getInstance();
        if ($ticket->getFromDB($fup->input['items_id'])
            && $fup->input['itemtype'] == 'Ticket') {
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
            if ($config->getField('addfup_updatetech')
                && Session::haveRight('ticket', UPDATE)) {
                $ticket_user = new Ticket_User();
                $ticket_user->getFromDBByCrit([
                    'tickets_id' => $ticket->getID(),
                    'type' => CommonITILActor::ASSIGN,
                ]);

                if (!isset($ticket_user->fields['users_id'])
                    || (isset($ticket_user->fields['users_id'])
                    && $ticket_user->fields['users_id'] <> Session::getLoginUserID())) {

                    $group_ticket = new Group_Ticket();
                    $group_ticket->getFromDBByCrit([
                        'tickets_id' => $ticket->getID(),
                        'type' => CommonITILActor::ASSIGN,
                    ]);
                    if (count($group_ticket->fields) > 0) {
                        if (isset($group_ticket->fields['groups_id'])) {
                            $usergroup = Group_User::getGroupUsers($group_ticket->fields['groups_id']);
                            $users = [];
                            foreach ($usergroup as $user) {
                                $users[$user['id']] = $user['id'];
                            }

                            if (in_array(Session::getLoginUserID(), $users)) {

                                if (isset($ticket_user->fields['users_id'])) {
                                    $ticket_user_delete = new Ticket_User();
                                    $ticket_user_delete->deleteByCriteria([
                                        'tickets_id' => $ticket->getID(),
                                        'users_id' => $ticket_user->fields['users_id'],
                                        'type' => CommonITILActor::ASSIGN,
                                    ]);
                                }

                                $ticket_user = new Ticket_User();
                                $ticket_user->add([
                                    'tickets_id' => $ticket->getID(),
                                    'users_id' => Session::getLoginUserID(),
                                    'type' => CommonITILActor::ASSIGN,
                                ]);
                            }
                        }
                    } else {
                        if (isset($ticket_user->fields['users_id'])) {
                            $ticket_user_delete = new Ticket_User();
                            $ticket_user_delete->deleteByCriteria([
                                'tickets_id' => $ticket->getID(),
                                'users_id' => $ticket_user->fields['users_id'],
                                'type' => CommonITILActor::ASSIGN,
                            ]);
                        }

                        $ticket_user->add([
                            'tickets_id' => $ticket->getID(),
                            'users_id' => Session::getLoginUserID(),
                            'type' => CommonITILActor::ASSIGN,
                        ]);
                    }
                }
            }
        }
    }
}
