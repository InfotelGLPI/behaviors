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

use Session;

class TicketTask
{
    /**
     * @param TicketTask $taskticket
     * @return false|void
     */
    public static function beforeAdd(\TicketTask $taskticket)
    {
        if (!is_array($taskticket->input) || !count($taskticket->input)) {
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
        if ($config->getField('is_tickettaskcategory_mandatory')) {
            if (!isset($taskticket->input['taskcategories_id'])
                || $taskticket->input['taskcategories_id'] == 0) {
                $taskticket->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "You must define a category. it's mandatory",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
        }
    }


    /**
     * @param TicketTask $taskticket
     * @return false|void
     */
    public static function beforeUpdate(\TicketTask $taskticket)
    {
        if (!is_array($taskticket->input) || !count($taskticket->input)) {
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
        if ($config->getField('is_tickettaskcategory_mandatory')) {
            if (empty($taskticket->input['taskcategories_id'])
                && $taskticket->fields['taskcategories_id'] == 0) {
                $taskticket->input = false;
                Session::addMessageAfterRedirect(
                    __(
                        "You must define a category. it's mandatory",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
                return;
            }
        }
        if ($config->getField('is_tickettasktodo')) {
            $ticket = new \Ticket();
            if ($ticket->getFromDB($taskticket->fields['tickets_id'])) {
                if (in_array(
                    $ticket->fields['status'],
                    array_merge(
                        \Ticket::getSolvedStatusArray(),
                        \Ticket::getClosedStatusArray()
                    )
                )) {
                    Session::addMessageAfterRedirect(
                        __(
                            "You cannot change status of a task in a solved ticket",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                    unset($taskticket->input['state']);
                }
            }
        }
    }
}
