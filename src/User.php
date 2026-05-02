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
use DbUtils;

class User
{
    /**
     * @param $entity
     * @param $userid
     * @param $filter
     * @param $first
     * @return array|int|mixed
     */
    private static function getUserGroup($entity, $userid, $column = '', $first = true)
    {
        global $DB;

        $config = Config::getInstance();
        $dbu = new DbUtils();

        $where = [
            'glpi_groups_users.users_id' => $userid,
            $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
        ];
        if ($column !== '') {
            $where['glpi_groups.' . $column] = 1;
        }
        $query = [
            'SELECT' => ['glpi_groups' => ['id']],
            'FROM' => 'glpi_groups_users',
            'INNER JOIN' => [
                'glpi_groups' => [
                    'FKEY' => [
                        'glpi_groups' => 'id',
                        'glpi_groups_users' => 'groups_id',
                    ],
                ],
            ],
            'WHERE' => $where,
        ];

        $rep = [];
        foreach ($DB->request($query) as $data) {
            if ($first) {
                return $data['id'];
            }
            $rep[] = $data['id'];
        }
        return ($first ? 0 : $rep);
    }


    /**
     * @param $entity
     * @param $userid
     * @param $first
     * @return array|int|mixed
     */
    public static function getRequesterGroup($entity, $userid, $first = true)
    {
        return self::getUserGroup($entity, $userid, 'is_requester', $first);
    }


    /**
     * @param $entity
     * @param $userid
     * @param $first
     * @return array|int|mixed
     */
    public static function getTechnicianGroup($entity, $userid, $first = true)
    {
        return self::getUserGroup($entity, $userid, 'is_assign', $first);
    }
}
