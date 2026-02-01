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

use GlpiPlugin\Behaviors\Common;
use GlpiPlugin\Behaviors\Computer;
use GlpiPlugin\Behaviors\Config;
use GlpiPlugin\Behaviors\Document_Item;
use GlpiPlugin\Behaviors\Group_Ticket;
use GlpiPlugin\Behaviors\ITILSolution;
use GlpiPlugin\Behaviors\ITILFollowup;
use GlpiPlugin\Behaviors\Supplier_Ticket;
use GlpiPlugin\Behaviors\Ticket;
use GlpiPlugin\Behaviors\Ticket_User;
use GlpiPlugin\Behaviors\TicketTask;
use GlpiPlugin\Behaviors\Change;
use GlpiPlugin\Behaviors\ChangeTask;
use GlpiPlugin\Behaviors\Problem;
use GlpiPlugin\Behaviors\ProblemTask;

define('PLUGIN_BEHAVIORS_VERSION', '3.0.2');
// Init the hooks of the plugins -Needed
function plugin_init_behaviors()
{
    global $PLUGIN_HOOKS;

    Plugin::registerClass(Config::class, ['addtabon' => 'Config']);
    $PLUGIN_HOOKS['config_page']['behaviors'] = 'front/config.form.php';

    $PLUGIN_HOOKS['item_add']['behaviors']
        = [
            \Ticket_User::class => [Ticket_User::class, 'afterAdd'],
            \Group_Ticket::class => [Group_Ticket::class, 'afterAdd'],
            \Supplier_Ticket::class => [Supplier_Ticket::class, 'afterAdd'],
            \Document_Item::class => [Document_Item::class, 'afterAdd'],
            \ITILSolution::class => [ITILSolution::class, 'afterAdd'],
        ];

    $PLUGIN_HOOKS['item_update']['behaviors']
        = [\Ticket::class => [Ticket::class, 'afterUpdate']];

    $PLUGIN_HOOKS['pre_item_add']['behaviors']
        = [
            \Ticket::class => [Ticket::class, 'beforeAdd'],
            \ITILSolution::class => [ITILSolution::class, 'beforeAdd'],
            \TicketTask::class => [TicketTask::class, 'beforeAdd'],
            \Change::class => [Change::class, 'beforeAdd'],
            \ITILFollowup::class => [ITILFollowup::class, 'beforeAdd'],
        ];

    $PLUGIN_HOOKS['post_prepareadd']['behaviors']
        = [\Ticket::class => [Ticket::class, 'afterPrepareAdd']];

    $PLUGIN_HOOKS['pre_item_update']['behaviors']
        = [
            \Problem::class => [Problem::class, 'beforeUpdate'],
            \Ticket::class => [Ticket::class, 'beforeUpdate'],
            \Change::class => [Change::class, 'beforeUpdate'],
            \ITILSolution::class => [ITILSolution::class, 'beforeUpdate'],
            \TicketTask::class => [TicketTask::class, 'beforeUpdate'],
            \ChangeTask::class => [ChangeTask::class, 'beforeUpdate'],
            \ProblemTask::class => [ProblemTask::class, 'beforeUpdate'],
        ];

    $PLUGIN_HOOKS['pre_item_purge']['behaviors']
        = [\Computer::class => [Computer::class, 'beforePurge']];

    $PLUGIN_HOOKS['item_purge']['behaviors']
        = [\Document_Item::class => [Document_Item::class, 'afterPurge']];

    // Notifications
    $PLUGIN_HOOKS['item_get_events']['behaviors']
        = ['NotificationTargetTicket' => [Ticket::class, 'addEvents']];

    $PLUGIN_HOOKS['item_add_targets']['behaviors']
        = ['NotificationTargetTicket' => [Ticket::class, 'addTargets']];

    $PLUGIN_HOOKS['item_action_targets']['behaviors']
        = ['NotificationTargetTicket' => [Ticket::class, 'addActionTargets']];

    $PLUGIN_HOOKS['pre_item_form']['behaviors'] = [Common::class, 'messageWarning'];
    $PLUGIN_HOOKS['post_item_form']['behaviors'] = [Common::class, 'deleteAddSolutionButton'];

    // End init, when all types are registered
    $PLUGIN_HOOKS['post_init']['behaviors'] = [Common::class, 'postInit'];

    $PLUGIN_HOOKS['csrf_compliant']['behaviors'] = true;

    //TO Disable in v11
    //    foreach ($CFG_GLPI["asset_types"] as $type) {
    //        $PLUGIN_HOOKS['item_can']['behaviors'][$type] = [$type => ['Config', 'item_can']];
    //    }

    //TO Disable in v11
    //    $PLUGIN_HOOKS['add_default_where']['behaviors'] = ['Config', 'add_default_where'];
}


function plugin_version_behaviors()
{
    return [
        'name' => __('Behaviours', 'behaviors'),
        'version' => PLUGIN_BEHAVIORS_VERSION,
        'license' => 'AGPLv3+',
        'author' => "<a href='https//blogglpi.infotel.com'>Infotel</a>, Xavier CAILLAUD, Remi COLLET, Nelly MAHU-LASSON",
        'homepage' => 'https://github.com/InfotelGLPI/behaviors',
        'minGlpiVersion' => '11.0.0',
        'requirements' => [
            'glpi' => [
                'min' => '11.0.0',
                'max' => '12.0.0',
            ],
        ],
    ];
}

function plugin_behaviors_geturl(): string
{
    /** @var array $CFG_GLPI */
    global $CFG_GLPI;
    return sprintf('%s/plugins/behaviors/', $CFG_GLPI['url_base']);
}
