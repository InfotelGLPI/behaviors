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

use CommonGLPI;
use CommonITILActor;
use DbUtils;
use Dropdown;
use Html;
use Log;
use Glpi\Application\View\TemplateRenderer;
use Plugin;
use Session;
use Toolbox;

class Common extends CommonGLPI
{
    public static $clone_types = [
        'NotificationTemplate' => NotificationTemplate::class,
        'Profile' => Profile::class,
        'RuleImportComputer' => Rule::class,
        'RuleImportEntity' => Rule::class,
        'RuleMailCollector' => Rule::class,
        'RuleRight' => Rule::class,
        'RuleSoftwareCategory' => Rule::class,
        'RuleTicket' => Rule::class,
        'Transfer' => Common::class,
        'Ticket' => Ticket::class,
    ];


    public static function getCloneTypes()
    {
        return self::$clone_types;
    }


    /**
     * Declare that a type is clonable
     *
     * @param $clonetype    String   classe name of new clonable type
     * @param $managertype  String   class name which manage the clone actions (default '')
     *
     * @return Boolean
     **/
    public static function addCloneType($clonetype, $managertype = '')
    {
        if (!isset(self::$clone_types[$clonetype])) {
            self::$clone_types[$clonetype] = ($managertype ? $managertype : $clonetype);
            return true;
        }
        // already registered
        return false;
    }


    /**
     * @return void
     */
    public static function postInit()
    {
        Plugin::registerClass(
            Common::class,
            ['addtabon' => array_keys(Common::getCloneTypes())]
        );

        Ticket::onNewTicket();
    }

    public static function getIcon()
    {
        return "ti ti-settings";
    }
    /**
     * @param CommonGLPI $item
     * @param $withtemplate
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $config = Config::getInstance();

        if (array_key_exists($item->getType(), self::$clone_types)
            && $item->canUpdate()
            && ($config->getField('clone') > 0)
            && (isset($_SESSION['glpiactiveprofile']['interface'])
                && ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'))) {
            return self::createTabEntry(
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Clone', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
        }
        return '';
    }


    /**
     * @param CommonGLPI $item
     * @return void
     */
     	public static function showCloneForm(CommonGLPI $item)
	{
    		$config = Config::getInstance();
    		$entity_name = null;

    		if ($item->isEntityAssign()) {
        		if ($config->getField('clone') == 1) {
            			$entities_id = $_SESSION['glpiactive_entity'];
        		} elseif ($config->getField('clone') == 2) {
            			$entities_id = $item->getEntityID();
        		}
        		if (isset($entities_id)) {
            			$entity_name = Dropdown::getDropdownName('glpi_entities', $entities_id);
        		}
    		}

    		TemplateRenderer::getInstance()->display(
        		'@behaviors/clone_form.html.twig',
        		[
            			'action'      => Toolbox::getItemTypeFormURL(__CLASS__),
            			'itemtype'    => $item->getType(),
            			'id'          => $item->getID(),
            			'name'        => sprintf(__('%1$s %2$s'), __('Clone of', 'behaviors'), $item->getName()),
            			'entity_name' => $entity_name,
        		]
    		);
	}


    /**
     * @param CommonGLPI $item
     * @param $tabnum
     * @param $withtemplate
     * @return true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (array_key_exists($item->getType(), self::$clone_types)
            && $item->canUpdate()) {
            self::showCloneForm($item);
        }
        return true;
    }


    /**
     * @param array $param
     * @return false|void
     */
    public static function cloneItem(array $param)
    {
        $dbu = new DbUtils();
        // Sanity check
        if (!isset($param['itemtype']) || !isset($param['id']) || !isset($param['name'])
            || !array_key_exists($param['itemtype'], self::$clone_types)
            || empty($param['name'])
            || !($item = $dbu->getItemForItemtype($param['itemtype']))) {
            return false;
        }

        // Read original and prepare clone
        $item->check($param['id'], READ);

        $input = $item->fields;
        $input['name'] = $param['name'];
        $input['_add'] = 1;
        $input['_old_id'] = $input['id'];
        unset($input['id']);
        if ($item->isEntityAssign()) {
            $config = Config::getInstance();

            if ($config->getField('clone') == 1) {
                $entities_id = $_SESSION['glpiactive_entity'];
            } elseif ($config->getField('clone') == 2) {
                $entities_id = $item->getEntityID();
            }
            $input['entities_id'] = $entities_id;
        }

        // Manage NULL fields in original
        foreach ($input as $k => $v) {
            if (is_null($input[$k])) {
                $input[$k] = "NULL";
            }
        }

        // Specific to itemtype - before clone
        if (method_exists(self::$clone_types[$param['itemtype']], 'preClone')) {
            $input = call_user_func(
                [self::$clone_types[$param['itemtype']], 'preClone'],
                $item,
                $input
            );
        }

        // Clone
        $clone = clone $item;
        $clone->check(-1, CREATE, $input);
        $new = $clone->add($input);

        // Specific to itemtype - after clone
        if (method_exists(self::$clone_types[$param['itemtype']], 'postClone')) {
            call_user_func([self::$clone_types[$param['itemtype']], 'postClone'], $clone, $param['id']);
        }
        Plugin::doHook('item_clone', $clone);

        // History
        if ($clone->dohistory) {
            $changes[0] = '0';
            $changes[1] = '';
            $changes[2] = addslashes(
                sprintf(
                    __('%1$s %2$s'),
                    __('Clone of', 'behaviors'),
                    $item->getNameID(0, true)
                )
            );
            Log::history(
                $clone->getID(),
                $clone->getType(),
                $changes,
                0,
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );
        }
    }

    /**
     * show warning message
     *
     * @param $params
     *
     * @return false
     **/
    public static function checkWarnings($params)
    {
        global $DB;

        $warnings = [];
        $obj = $params['options']['item'];

        $config = Config::getInstance();

        // Check is the connected user is a tech
        if (!is_numeric(Session::getLoginUserID(false))
            || (!(Session::haveRight('ticket', UPDATE) || Session::haveRight('ITILSolution', CREATE))
                && !Session::haveRight('problem', UPDATE)
                && !Session::haveRight('change', UPDATE))) {
            return false; // No check
        }

        // Want to solve/close the ticket
        $dur = ($obj->fields['actiontime'] ?? 0);
        $cat = ($obj->fields['itilcategories_id'] ?? 0);
        $loc = ($obj->fields['locations_id'] ?? 0);

        if ($obj->getType() == 'Ticket') {
            $mandatory_solution = false;
            if ($config->getField('is_ticketrealtime_mandatory')) {
                // for moreTicket plugin
		$plugin = new Plugin();
		if ($plugin->isActivated('moreticket') && class_exists(\GlpiPlugin\Moreticket\Config::class)) {
    		    $configmoreticket = new \GlpiPlugin\Moreticket\Config();
                    $mandatory_solution = $configmoreticket->isMandatorysolution();
		}

                if (($dur == 0) && ($mandatory_solution == false)) {
                    $warnings[] = __("Duration is mandatory before ticket is solved/closed", 'behaviors');
                }
            }
            if ($config->getField('is_ticketcategory_mandatory')) {
                if ($cat == 0) {
                    $warnings[] = __("Category is mandatory before ticket is solved/closed", 'behaviors');
                }
            }

            if ($config->getField('is_tickettech_mandatory')) {
                if (($obj->countUsers(CommonITILActor::ASSIGN) == 0)
                    && !$config->getField('ticketsolved_updatetech')) {
                    $warnings[] = __(
                        "Technician assigned is mandatory before ticket is solved/closed",
                        'behaviors'
                    );
                }
            }

            if ($config->getField('is_tickettechgroup_mandatory')) {
                if (($obj->countGroups(CommonITILActor::ASSIGN) == 0)) {
                    $warnings[] = __(
                        "Group of technicians assigned is mandatory before ticket is solved/closed",
                        'behaviors'
                    );
                }
            }

            if ($config->getField('is_ticketlocation_mandatory')) {
                if ($loc == 0) {
                    $warnings[] = __("Location is mandatory before ticket is solved/closed", 'behaviors');
                }
            }

            if ($config->getField('is_tickettasktodo')) {
                $crit = [
                    'FROM' => 'glpi_tickettasks',
                    'WHERE' => [
                        'tickets_id' => $obj->getField('id'),
                    ],
                ];
                foreach ($DB->request($crit) as $task
                ) {
                    if ($task['state'] == 1) {
                        $warnings[] = __("You cannot solve/close a ticket with task do to", 'behaviors');
                        break;
                    }
                }
            }
        }

        if ($obj->getType() == 'Problem') {
            if ($config->getField('is_problemtasktodo')) {
                $crit = [
                    'FROM' => 'glpi_problemtasks',
                    'WHERE' => [
                        'problems_id' => $obj->getField('id'),
                    ],
                ];
                foreach ($DB->request($crit) as $task
                ) {
                    if ($task['state'] == 1) {
                        $warnings[] = __("You cannot solve/close a problem with task do to", 'behaviors');
                        break;
                    }
                }
            }
        }

        if ($obj->getType() == 'Change') {
            if ($config->getField('is_changetasktodo')) {
                $crit = [
                    'FROM' => 'glpi_changetasks',
                    'WHERE' => [
                        'changes_id' => $obj->getField('id'),
                    ],
                ];

                foreach ($DB->request(
                    $crit
                ) as $task
                ) {
                    if ($task['state'] == 1) {
                        $warnings[] = __("You cannot solve/close a change with task do to", 'behaviors');
                        break;
                    }
                }
            }
        }
        return $warnings;
    }


    /**
     * Displaying message solution
     *
     * @param $params
     **/
     public static function messageWarning($params)
     {
     	if (isset($params['item'])) {
        	$item = $params['item'];

        	if ($item->getType() == 'ITILSolution') {
            		$warnings = self::checkWarnings($params);
            		$config = Config::getInstance();
            		$parentitem = $params['options']['item'];

            		$show_solution_mandatory = $config->getField('is_ticketsolution_mandatory')
                	   && is_array($warnings)
                	   && count($warnings) == 0
                           && $parentitem->getType() == 'Ticket';

            		$show_solutiontype_mandatory = $config->getField('is_ticketsolutiontype_mandatory')
                	   && is_array($warnings)
                           && count($warnings) == 0;

            		if ($show_solution_mandatory || $show_solutiontype_mandatory || (is_array($warnings) && count($warnings))) {
                		TemplateRenderer::getInstance()->display(
                    			'@behaviors/warning_solution.html.twig',
                    			[
                        			'warnings'                  => is_array($warnings) ? $warnings : [],
                        			'parent_type'               => $parentitem->getType(),
                        			'show_solution_mandatory'   => $show_solution_mandatory,
                        			'show_solutiontype_mandatory' => $show_solutiontype_mandatory,
                    			]
                		);
            		}
        	} elseif ($item->getType() == 'TicketTask') {
            		$config = Config::getInstance();
            		if ($config->getField('is_tickettaskcategory_mandatory')) {
                		TemplateRenderer::getInstance()->display('@behaviors/warning_task.html.twig', []);
            		}
        	}
    	}
    	return $params;
     }



    /**
     * Displaying Add solution button or not
     *
     * @param $params
     *
     * @return array
     /**/
	public static function deleteAddSolutionButton($params)
	{
    		if (isset($params['item'])) {
        		$item = $params['item'];
        		if ($item->getType() == 'ITILSolution') {
            			$warnings = self::checkWarnings($params);
            			if (is_array($warnings) && count($warnings) > 0) {
                			TemplateRenderer::getInstance()->display(
                    			'@behaviors/warning_hide_submit.html.twig',
                    			[]
                			);
            			}
        		}
    		}
	}

}
