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

class PluginBehaviorsNotificationTemplate extends PluginBehaviorsCommon
{
    /**
     * @param NotificationTemplate $clone
     * @param $oldid
     * @return void
     */
    public static function postClone(NotificationTemplate $clone, $oldid)
    {
        global $DB;

        $trad = new NotificationTemplateTranslation();
        $dbu = new DbUtils();
        $fkey = $dbu->getForeignKeyFieldForTable($clone->getTable());
        $crit = [$fkey => $oldid];

        foreach ($DB->request($trad->getTable(), $crit) as $data) {
            unset($data['id']);
            $data[$fkey] = $clone->getID();
            $trad->add(Toolbox::addslashes_deep($data));
        }
    }
}
