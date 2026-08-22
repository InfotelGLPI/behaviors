<?php

/**
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
 * @author    Infotel, Remi Collet, Nelly Mahu-Lasson
 * @copyright Copyright (c) 2018-2026 Behaviors plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/InfotelGLPI/behaviors/
 * @link      http://www.glpi-project.org/
 * @package   behaviors
 * @since     2010
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Behaviors;

use Glpi\Exception\Http\AccessDeniedHttpException;
use ProfileRight;

class Profile extends Common
{
    /**
     * @param Profile $srce
     * @param array $input
     * @return array
     */
    public static function preClone(\Profile $srce, array $input)
    {
        // Prevent privilege escalation. postClone() copies every ProfileRight of
        // the source profile verbatim into the clone, without re-checking that the
        // operator actually holds those rights. Deny the clone unless the current
        // operator already has at least all the rights of the source profile -
        // this reuses GLPI core's own guard for profile editing/creation, so a
        // delegated "profile manager" cannot duplicate a higher-privileged profile
        // (e.g. Super-Admin) and grant themselves rights they do not possess.
        if (!\Profile::currentUserHaveMoreRightThan([(int) $srce->getID()])) {
            throw new AccessDeniedHttpException();
        }

        // decode array
        if (isset($input['helpdesk_item_type'])
            && !is_array($input['helpdesk_item_type'])) {
            $input['helpdesk_item_type'] = importArrayFromDB($input['helpdesk_item_type']);
        }

        // Empty/NULL case
        if (!isset($input['helpdesk_item_type'])
            || !is_array($input['helpdesk_item_type'])) {
            $input['helpdesk_item_type'] = [];
        }

        if (!isset($input['managed_domainrecordtypes'])
            || !is_array($input['managed_domainrecordtypes'])) {
            $input["managed_domainrecordtypes"] = [];
        }

        return $input;
    }


    /**
     * @param $clone      Profile item
     * @param $oldid
     * @since version 0.90.1
     *
     */
    public static function postClone(\Profile $clone, $oldid)
    {
        $rights = ProfileRight::getProfileRights($oldid);
        $pright = new ProfileRight();
        $pright->updateProfileRights($clone->getID(), $rights);
    }
}
