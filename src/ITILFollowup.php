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

namespace GlpiPlugin\Behaviors;
use CommonITILActor;
use Session;

class ITILFollowup
{
    /**
     * @param ITILFollowup $fup
     * @return void
     */
    public static function beforeAdd(\ITILFollowup $fup)
    {
        $ticket = new \Ticket();
        $config = Config::getInstance();
        if ($ticket->getFromDB($fup->input['items_id'])
            && $fup->input['itemtype'] == 'Ticket') {
            if ($config->getField('addfup_updatetech')
                && Session::haveRight('ticket', UPDATE)) {
                $ticket_user = new \Ticket_User();

                if ($fields = $ticket_user->find(['tickets_id' => $ticket->getID(),
                    'type' => CommonITILActor::ASSIGN])) {

                    foreach ($fields as $field) {

                        if (!isset($field['users_id'])
                            || (isset($field['users_id'])
                                && $field['users_id'] <> Session::getLoginUserID())) {

                            $group_ticket = new \Group_Ticket();
                            $group_ticket->getFromDBByCrit([
                                'tickets_id' => $ticket->getID(),
                                'type' => CommonITILActor::ASSIGN,
                            ]);
                            if (count($group_ticket->fields) > 0) {
                                if (isset($group_ticket->fields['groups_id'])) {
                                    $usergroup = \Group_User::getGroupUsers($group_ticket->fields['groups_id']);
                                    $users = [];
                                    foreach ($usergroup as $user) {
                                        $users[$user['id']] = $user['id'];
                                    }

                                    if (in_array(Session::getLoginUserID(), $users)) {

                                        if (isset($field['users_id'])) {
                                            $ticket_user_delete = new \Ticket_User();
                                            $ticket_user_delete->deleteByCriteria([
                                                'tickets_id' => $ticket->getID(),
                                                'users_id' => $field['users_id'],
                                                'type' => CommonITILActor::ASSIGN,
                                            ]);
                                        }

                                        $ticket_user = new \Ticket_User();
                                        $ticket_user->add([
                                            'tickets_id' => $ticket->getID(),
                                            'users_id' => Session::getLoginUserID(),
                                            'type' => CommonITILActor::ASSIGN,
                                        ]);
                                    }
                                }
                            } else {
                                if (isset($field['users_id'])) {
                                    $ticket_user_delete = new \Ticket_User();
                                    $ticket_user_delete->deleteByCriteria([
                                        'tickets_id' => $ticket->getID(),
                                        'users_id' => $field['users_id'],
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
    }
}
