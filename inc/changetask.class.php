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

class PluginBehaviorsChangeTask
{
    /**
     * @param ChangeTask $task
     * @return false|void
     */
    public static function beforeUpdate(ChangeTask $task)
    {
        if (!is_array($task->input) || !count($task->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = PluginBehaviorsConfig::getInstance();

        // Check is the connected user is a tech
        if (!is_numeric(Session::getLoginUserID(false))
            || !Session::haveRight('change', UPDATE)) {
            return false; // No check
        }

        if ($config->getField('is_changetasktodo')) {
            $change = new Change();
            if ($change->getFromDB($task->fields['changes_id'])) {
                if (in_array(
                    $change->fields['status'],
                    array_merge(
                        Change::getSolvedStatusArray(),
                        Change::getClosedStatusArray()
                    )
                )) {
                    Session::addMessageAfterRedirect(
                        __(
                            "You cannot change status of a task in a solved change",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                    unset($task->input['state']);
                }
            }
        }
    }
}
