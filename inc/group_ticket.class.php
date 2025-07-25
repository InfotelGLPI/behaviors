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

class PluginBehaviorsGroup_Ticket
{
    /**
     * @param Group_Ticket $item
     * @return false|void
     */
    public static function afterAdd(Group_Ticket $item)
    {
        global $DB;

        // Check is the connected user is a tech
        if (!is_numeric(Session::getLoginUserID(false))
            || !Session::haveRight('ticket', Ticket::OWN)) {
            return false; // No check
        }

        $config = PluginBehaviorsConfig::getInstance();
        if (($config->getField('single_tech_mode') != 0)
            && ($item->input['type'] == CommonITILActor::ASSIGN)) {
            $crit = [
                'tickets_id' => $item->input['tickets_id'],
                'type' => CommonITILActor::ASSIGN,
            ];

            foreach ($DB->request('glpi_groups_tickets', $crit) as $data) {
                if ($data['id'] != $item->getID()) {
                    $gu = new Group_Ticket();
                    $gu->delete($data);
                }
            }

            if ($config->getField('single_tech_mode') == 2) {
                foreach ($DB->request('glpi_tickets_users', $crit) as $data) {
                    $gu = new Ticket_User();
                    $gu->delete($data);
                }
            }
        }
    }
}
