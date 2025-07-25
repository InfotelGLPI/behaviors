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

define('PLUGIN_BEHAVIORS_VERSION', '2.7.8');
// Init the hooks of the plugins -Needed
function plugin_init_behaviors()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    Plugin::registerClass('PluginBehaviorsConfig', ['addtabon' => 'Config']);
    $PLUGIN_HOOKS['config_page']['behaviors'] = 'front/config.form.php';

    $PLUGIN_HOOKS['item_add']['behaviors'] =
        [
            'Ticket_User' => ['PluginBehaviorsTicket_User', 'afterAdd'],
            'Group_Ticket' => ['PluginBehaviorsGroup_Ticket', 'afterAdd'],
            'Supplier_Ticket' => ['PluginBehaviorsSupplier_Ticket', 'afterAdd'],
            'Document_Item' => ['PluginBehaviorsDocument_Item', 'afterAdd'],
            'ITILSolution' => ['PluginBehaviorsITILSolution', 'afterAdd'],
        ];

    $PLUGIN_HOOKS['item_update']['behaviors'] =
        ['Ticket' => ['PluginBehaviorsTicket', 'afterUpdate']];

    $PLUGIN_HOOKS['pre_item_add']['behaviors'] =
        [
            'Ticket' => ['PluginBehaviorsTicket', 'beforeAdd'],
            'ITILSolution' => ['PluginBehaviorsITILSolution', 'beforeAdd'],
            'TicketTask' => ['PluginBehaviorsTickettask', 'beforeAdd'],
            'Change' => ['PluginBehaviorsChange', 'beforeAdd'],
            'ITILFollowup' => ['PluginBehaviorsITILFollowup', 'beforeAdd'],
        ];

    $PLUGIN_HOOKS['post_prepareadd']['behaviors'] =
        ['Ticket' => ['PluginBehaviorsTicket', 'afterPrepareAdd']];

    $PLUGIN_HOOKS['pre_item_update']['behaviors'] =
        [
            'Problem' => ['PluginBehaviorsProblem', 'beforeUpdate'],
            'Ticket' => ['PluginBehaviorsTicket', 'beforeUpdate'],
            'Change' => ['PluginBehaviorsChange', 'beforeUpdate'],
            'ITILSolution' => ['PluginBehaviorsITILSolution', 'beforeUpdate'],
            'TicketTask' => ['PluginBehaviorsTickettask', 'beforeUpdate'],
            'ChangeTask' => ['PluginBehaviorsChangetask', 'beforeUpdate'],
            'ProblemTask' => ['PluginBehaviorsProblemtask', 'beforeUpdate'],
        ];

    $PLUGIN_HOOKS['pre_item_purge']['behaviors'] =
        ['Computer' => ['PluginBehaviorsComputer', 'beforePurge']];

    $PLUGIN_HOOKS['item_purge']['behaviors'] =
        ['Document_Item' => ['PluginBehaviorsDocument_Item', 'afterPurge']];

    // Notifications
    $PLUGIN_HOOKS['item_get_events']['behaviors'] =
        ['NotificationTargetTicket' => ['PluginBehaviorsTicket', 'addEvents']];

    $PLUGIN_HOOKS['item_add_targets']['behaviors'] =
        ['NotificationTargetTicket' => ['PluginBehaviorsTicket', 'addTargets']];

    $PLUGIN_HOOKS['item_action_targets']['behaviors'] =
        ['NotificationTargetTicket' => ['PluginBehaviorsTicket', 'addActionTargets']];

    $PLUGIN_HOOKS['pre_item_form']['behaviors'] = [PluginBehaviorsCommon::class, 'messageWarning'];
    $PLUGIN_HOOKS['post_item_form']['behaviors'] = [PluginBehaviorsCommon::class, 'deleteAddSolutionButton'];

    // End init, when all types are registered
    $PLUGIN_HOOKS['post_init']['behaviors'] = ['PluginBehaviorsCommon', 'postInit'];

    $PLUGIN_HOOKS['csrf_compliant']['behaviors'] = true;

    //TO Disable in v11
    foreach ($CFG_GLPI["asset_types"] as $type) {
        $PLUGIN_HOOKS['item_can']['behaviors'][$type] = [$type => ['PluginBehaviorsConfig', 'item_can']];
    }

    //TO Disable in v11
    $PLUGIN_HOOKS['add_default_where']['behaviors'] = ['PluginBehaviorsConfig', 'add_default_where'];
}


function plugin_version_behaviors()
{
    return [
        'name' => __('Behaviours', 'behaviors'),
        'version' => PLUGIN_BEHAVIORS_VERSION,
        'license' => 'AGPLv3+',
        'author' => 'Infotel, Remi Collet, Nelly Mahu-Lasson',
        'homepage' => 'https://github.com/InfotelGLPI/behaviors',
        'minGlpiVersion' => '10.0.5',
        'requirements' => [
            'glpi' => [
                'min' => '10.0.5',
                'max' => '11.0.0',
            ],
        ],
    ];
}
