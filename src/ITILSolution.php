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
use AllowDynamicProperties;
use CommonITILActor;
use Session;

#[AllowDynamicProperties]
class ITILSolution
{
    /**
     * @param ITILSolution $soluce
     * @return false|void
     */
    public static function beforeAdd(\ITILSolution $soluce)
    {
        global $DB;

        if (!is_array($soluce->input) || !count($soluce->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = Config::getInstance();

        // Check is the connected user is a tech
        if (!is_numeric(Session::getLoginUserID(false))
            || !Session::haveRight('ticket', UPDATE)) {
            return false; // No check
        }

        // Want to solve/close the ticket
        $ticket = new \Ticket();
        if ($ticket->getFromDB($soluce->input['items_id'])
            && ($soluce->input['itemtype'] == 'Ticket')) {
            if ($config->getField('is_ticketsolutiontype_mandatory')
                && empty($soluce->input['solutiontypes_id'])) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Type of solution is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_ticketsolution_mandatory')
                && empty($soluce->input['content'])) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Description of solution is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_ticketrealtime_mandatory')
                && ($ticket->fields['actiontime'] == 0)) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Duration is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            //            if ($config->getField('is_ticketrealtime_mandatory')
            //                && $soluce->input['itemtype'] == 'Ticket') {
            //
            //                if (isset($soluce->input['duration_solution'])
            //                    && $soluce->input['duration_solution'] > 0) {
            //                    $ticket = new \Ticket();
            //                    $tickets_id = $soluce->input['items_id'];
            //                    if ($ticket->getFromDB($tickets_id)) {
            //                        if ($ticket->getField('actiontime') == 0) {
            //                            $ticket->update([
            //                                'id' => $tickets_id,
            //                                'actiontime' => $soluce->input['duration_solution']
            //                            ]);
            //                        }
            //                    }
            //
            //                    $user = new User();
            //                    $user->getFromDB(Session::getLoginUserID());
            //
            //                    $tickettask = new TicketTask();
            //                    $tickettask->add([
            //                        'tickets_id' => $tickets_id,
            //                        'date_creation' => date('Y-m-d H:i:s'),
            //                        'date' => date(
            //                            'Y-m-d H:i:s',
            //                            strtotime('- 10 seconds', strtotime(date('Y-m-d H:i:s')))
            //                        ),
            //                        'users_id' => Session::getLoginUserID(),
            //                        'users_id_tech' => Session::getLoginUserID(),
            //                        'content' => $soluce->input['content'],
            //                        'state' => Planning::DONE,
            //                        'is_private' => $user->getField('task_private'),
            //                        'actiontime' => $soluce->input['duration_solution']
            //                    ]);
            //                } else {
            //                    $configglpi = Config::getConfigurationValues('core', ['system_user']);
            //                    if (isset($soluce->input['users_id'])
            //                        && $configglpi['system_user'] == $soluce->input['users_id']) {
            //                        return true;
            //                    }
            //                    $ticket = new \Ticket();
            //                    $tickets_id = $soluce->input['items_id'];
            //                    $ticket->getFromDB($tickets_id);
            //                    $dur = (isset($ticket->fields['actiontime']) ? $ticket->fields['actiontime'] : 0);
            //                    if ($dur == 0) {
            //                        $_SESSION['saveInput'][$soluce->getType()] = $soluce->input;
            //                        $soluce->input = false;
            //                        Session::addMessageAfterRedirect(
            //                            __(
            //                                "Duration is mandatory before ticket is solved/closed",
            //                                'behaviors'
            //                            ),
            //                            true,
            //                            ERROR
            //                        );
            //                        return;
            //                    }
            //                }
            //            }
            if ($config->getField('is_ticketcategory_mandatory')
                && ($ticket->fields['itilcategories_id'] == 0)) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Category is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_tickettech_mandatory')
                && ($ticket->countUsers(CommonITILActor::ASSIGN) == 0)
                && !$config->getField('ticketsolved_updatetech')) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Technician assigned is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_tickettechgroup_mandatory')
                && ($ticket->countGroups(CommonITILActor::ASSIGN) == 0)) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Group of technicians assigned is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_ticketlocation_mandatory')
                && ($ticket->fields['locations_id'] == 0)) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Location is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_tickettasktodo')) {
                $crit = [
                    'FROM' => 'glpi_tickettasks',
                    'WHERE' => [
                        'tickets_id' => $ticket->getField('id')
                    ]
                ];
                foreach (
                    $DB->request($crit) as $task
                ) {
                    if ($task['state'] == 1) {
                        $soluce->input = false;
                        Session::addMessageAfterRedirect(
                            __(
                                "You cannot solve/close a ticket with task do to",
                                'behaviors'
                            ),
                            true,
                            ERROR
                        );
                        return;
                    }
                }
            }
        }

        // Want to solve/close the problem
        $problem = new \Problem();
        if ($problem->getFromDB($soluce->input['items_id'])
            && ($soluce->input['itemtype'] == 'Problem')) {
            if ($config->getField('is_problemsolutiontype_mandatory')
                && empty($soluce->input['solutiontypes_id'])) {
                $soluce->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "Type of solution is mandatory before problem is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
            if ($config->getField('is_problemtasktodo')) {
                $crit = [
                    'FROM' => 'glpi_problemtasks',
                    'WHERE' => [
                        'problems_id' => $problem->getField('id')
                    ]
                ];
                foreach (
                    $DB->request($crit) as $task
                ) {
                    if ($task['state'] == 1) {
                        $soluce->input = false;
                        Session::addMessageAfterRedirect(
                            __(
                                "You cannot solve/close a problem with task do to",
                                'behaviors'
                            ),
                            true,
                            ERROR
                        );
                        return;
                    }
                }
            }
        }

        // Want to solve/close the
        $change = new \Change();
        if ($change->getFromDB($soluce->input['items_id'])
            && $soluce->input['itemtype'] == 'Change') {
            if ($config->getField('is_changetasktodo')) {

                $crit = [
                    'FROM' => 'glpi_changetasks',
                    'WHERE' => [
                        'changes_id' => $change->getField('id')
                    ]
                ];

                foreach (
                    $DB->request(
                        $crit) as $task
                ) {
                    if ($task['state'] == 1) {
                        $soluce->input = false;
                        Session::addMessageAfterRedirect(
                            __(
                                "You cannot solve/close a change with task do to",
                                'behaviors'
                            ),
                            true,
                            ERROR
                        );
                        return;
                    }
                }
            }
        }
    }


    /**
     * @param ITILSolution $soluce
     * @return false|void
     */
    public static function beforeUpdate(\ITILSolution $soluce)
    {
        if (!is_array($soluce->input) || !count($soluce->input)) {
            // Already cancel by another plugin
            return false;
        }

        //Toolbox::logDebug("Ticket::beforeAdd(), Ticket=", $ticket);
        $config = Config::getInstance();

        // Check is the connected user is a tech
        if (!is_numeric(Session::getLoginUserID(false))
            || !Session::haveRight('ticket', UPDATE)) {
            return false; // No check
        }

        // Wand to solve/close the ticket
        if ($config->getField('is_ticketsolutiontype_mandatory')
            && $soluce->input['itemtype'] == 'Ticket') {
            if (empty($soluce->input['solutiontypes_id']) || ($soluce->input['solutiontypes_id'] == 0)) {
                $soluce->input['content'] = $soluce->fields['content'];
                $soluce->input['solutiontypes_id'] = $soluce->fields['solutiontypes_id'];
                Session::addMessageAfterRedirect(
                    __(
                        "Type of solution is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
            }
        }
        if ($config->getField('is_ticketsolution_mandatory')
            && $soluce->input['itemtype'] == 'Ticket') {
            if (empty($soluce->input['content'])) {
                $soluce->input['content'] = $soluce->fields['content'];
                $soluce->input['solutiontypes_id'] = $soluce->fields['solutiontypes_id'];
                Session::addMessageAfterRedirect(
                    __(
                        "Description of solution is mandatory before ticket is solved/closed",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
            }
        }
    }


    /**
     * @param ITILSolution $soluce
     * @return void
     */
    public static function afterAdd(\ITILSolution $soluce)
    {
        $ticket = new \Ticket();
        $config = Config::getInstance();
        if ($ticket->getFromDB($soluce->input['items_id'])
            && $soluce->input['itemtype'] == 'Ticket') {

            if ($config->getField('ticketsolved_updatetech')) {
                $ticket_user = new \Ticket_User();

                if ($fields = $ticket_user->find(['tickets_id' => $ticket->getID(),
                    'type' => CommonITILActor::ASSIGN])) {
                    if (count($fields) == 1) {
                        foreach ($fields as $f) {
                            if (isset($f['users_id'])
                                && $f['users_id'] <> Session::getLoginUserID()) {
                                $ticket_user->add([
                                    'tickets_id' => $ticket->getID(),
                                    'users_id' => Session::getLoginUserID(),
                                    'type' => CommonITILActor::ASSIGN,
                                ]);
                            }
                        }
                    } else if (count($fields) == 0) {
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
