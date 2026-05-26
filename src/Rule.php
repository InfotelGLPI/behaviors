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

use DbUtils;
use RuleAction;
use RuleCriteria;

class Rule extends Common
{
    /**
     * @param Rule $srce
     * @param array $input
     * @return array
     */
    public static function preClone(\Rule $srce, array $input)
    {
        $input['ranking'] = $srce->getNextRanking();
        return $input;
    }


    /**
     * @param Rule $clone
     * @param $oldid
     * @return void
     */
    public static function postClone(Rule $clone, $oldid)
    {
        global $DB;

        $dbu = new DbUtils();
        $fkey = $dbu->getForeignKeyFieldForTable($clone->getTable());
        $criteria = new RuleCriteria();
        $crit = [
            'FROM' => $criteria->getTable(),
            'WHERE' => [
                $fkey => $oldid
            ]
        ];

        foreach ($DB->request($crit) as $data) {
            unset($data['id']);
            $data[$fkey] = $clone->getID();
            $criteria->add($data);
        }
        $action = new RuleAction();
        $crit = [
            'FROM' => $action->getTable(),
            'WHERE' => [
                $fkey => $oldid
            ]
        ];

        foreach ($DB->request($crit) as $data) {
            unset($data['id']);
            $data[$fkey] = $clone->getID();
            $action->add($data);
        }
    }
}
