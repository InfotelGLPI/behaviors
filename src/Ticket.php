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

use CommonITILActor;
use CommonITILObject;
use DbUtils;
use Glpi\DBAL\QueryExpression;
use Item_Ticket;
use NotificationEvent;
use NotificationMailing;
use NotificationTargetTicket;
use Plugin;
use Session;
use Ticket_Ticket;
use UserEmail;

class Ticket
{
    public const LAST_TECH_ASSIGN = 50;
    public const LAST_GROUP_ASSIGN = 51;
    public const LAST_SUPPLIER_ASSIGN = 52;
    public const LAST_WATCHER_ADDED = 53;
    public const SUPERVISOR_LAST_GROUP_ASSIGN = 54;
    public const LAST_GROUP_ASSIGN_WITHOUT_SUPERVISOR = 55;


    /**
     * @param NotificationTargetTicket $target
     * @return void
     */
    public static function addEvents(NotificationTargetTicket $target)
    {
        $config = Config::getInstance();

        if ($config->getField('add_notif')) {
            Plugin::loadLang('behaviors');
            $target->events['plugin_behaviors_ticketreopen']
                = sprintf(
                    __('%1$s - %2$s'),
                    __('Behaviours', 'behaviors'),
                    __('Reopen ticket', 'behaviors')
                );

            $target->events['plugin_behaviors_ticketstatus']
                = sprintf(
                    __('%1$s - %2$s'),
                    __('Behaviours', 'behaviors'),
                    __('Change status', 'behaviors')
                );

            $target->events['plugin_behaviors_ticketwaiting']
                = sprintf(
                    __('%1$s - %2$s'),
                    __('Behaviours', 'behaviors'),
                    __('Ticket waiting', 'behaviors')
                );

            Document_Item::addEvents($target);
        }
    }


    /**
     * @param NotificationTargetTicket $target
     * @return void
     */
    public static function addTargets(NotificationTargetTicket $target)
    {
        // No new recipients for globals notifications
        $alert = ['alertnotclosed', 'recall', 'recall_ola'];
        if (!in_array($target->raiseevent, $alert)) {
            $target->addTarget(
                self::LAST_TECH_ASSIGN,
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Last technician assigned', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
            $target->addTarget(
                self::LAST_GROUP_ASSIGN,
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Last group assigned', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
            $target->addTarget(
                self::LAST_SUPPLIER_ASSIGN,
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Last supplier assigned', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
            $target->addTarget(
                self::LAST_WATCHER_ADDED,
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Last watcher added', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
            $target->addTarget(
                self::SUPERVISOR_LAST_GROUP_ASSIGN,
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Supervisor of last group assigned', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
            $target->addTarget(
                self::LAST_GROUP_ASSIGN_WITHOUT_SUPERVISOR,
                sprintf(
                    __('%1$s (%2$s)'),
                    __('Last group assigned without supersivor', 'behaviors'),
                    __('Behaviours', 'behaviors')
                )
            );
        }
    }


    /**
     * @param NotificationTargetTicket $target
     * @return void
     */
    public static function addActionTargets(NotificationTargetTicket $target)
    {
        switch ($target->data['items_id']) {
            case self::LAST_TECH_ASSIGN:
                self::getLastLinkedUserByType(CommonITILActor::ASSIGN, $target);
                break;

            case self::LAST_GROUP_ASSIGN:
                self::getLastLinkedGroupByType(CommonITILActor::ASSIGN, $target);
                break;

            case self::LAST_SUPPLIER_ASSIGN:
                self::getLastSupplierAddress($target);
                break;

            case self::LAST_WATCHER_ADDED:
                self::getLastLinkedUserByType(CommonITILActor::OBSERVER, $target);
                break;

            case self::SUPERVISOR_LAST_GROUP_ASSIGN:
                self::getLastLinkedGroupByType(CommonITILActor::ASSIGN, $target, 1);
                break;

            case self::LAST_GROUP_ASSIGN_WITHOUT_SUPERVISOR:
                self::getLastLinkedGroupByType(CommonITILActor::ASSIGN, $target, 2);
                break;
        }
    }


    /**
     * @param $type
     * @param $target
     * @return void
     */
    public static function getLastLinkedUserByType($type, $target)
    {
        global $DB, $CFG_GLPI;

        $dbu = new DbUtils();
        $userlinktable = $dbu->getTableForItemType($target->obj->userlinkclass);
        $fkfield = $target->obj->getForeignKeyField();

        $last = [
            'SELECT' => ['MAX' => 'id AS lastid'],
            'FROM' => $userlinktable,
            'WHERE' => [
                $userlinktable . '.' . $fkfield => $target->obj->fields["id"],
                $userlinktable . '.type' => $type,
            ],
        ];
        $result = $DB->request($last);

        $querylast = [];
        if ($data = $result->current()) {
            $object = new $target->obj->userlinkclass();
            if ($object->getFromDB($data['lastid'])) {
                $querylast = [$userlinktable.'.users_id' => $object->fields['users_id']];
            }
        }

        $criteria = [
            'SELECT' => ['glpi_users.id AS users_id',
                'glpi_users.language AS language',
                $userlinktable.'.use_notification AS notif',
                $userlinktable.'.alternative_email AS altemail',],
            'DISTINCT'        => true,
            'FROM' => $userlinktable,
            'INNER JOIN' => [
		'glpi_users' => [
		    'ON' => [
			$userlinktable => 'users_id',
			'glpi_users' => 'id'
		    ]
		],
	        'glpi_profiles_users' => [
                    'ON' => [
                        'glpi_profiles_users' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ],
            ],
            'WHERE' => [
                $userlinktable. '.' .$fkfield => $target->obj->fields["id"],
                $userlinktable.'.type' => $type,
            ]
        ];
        $criteria['WHERE'] = $criteria['WHERE'] + $querylast;
        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_profiles_users', 'entities_id', $target->getEntity(), true
            );

        foreach ($DB->request($criteria) as $data) {
            //Add the user email and language in the notified users list
            if ($data['notif']) {
                $author_email = UserEmail::getDefaultForUser($data['users_id']);
                $author_lang = $data["language"];
                $author_id = $data['users_id'];

                if (!empty($data['altemail'])
                    && ($data['altemail'] != $author_email)
                    && NotificationMailing::isUserAddressValid($data['altemail'])) {
                    $author_email = $data['altemail'];
                }
                if (empty($author_lang)) {
                    $author_lang = $CFG_GLPI["language"];
                }
                if (empty($author_id)) {
                    $author_id = -1;
                }
                $target->addToRecipientsList([
                    'email' => $author_email,
                    'language' => $author_lang,
                    'users_id' => $author_id,
                ]);
            }
        }

        // Anonymous user
        foreach (
            $DB->request([
                'SELECT' => 'alternative_email',
                'FROM' => $userlinktable,
                'WHERE' => [
                    $fkfield => $target->obj->fields["id"],
                    'users_id' => 0,
                    'use_notification' => 1,
                    'type' => $type,
                ],
            ]) as $data
        ) {
            if (NotificationMailing::isUserAddressValid($data['alternative_email'])) {
                $target->addToRecipientsList([
                    'email' => $data['alternative_email'],
                    'language' => $CFG_GLPI["language"],
                    'users_id' => -1,
                ]);
            }
        }
    }


    /**
     * @param $type
     * @param $target
     * @param $supervisor
     * @return void
     */
    public static function getLastLinkedGroupByType($type, $target, $supervisor = 0)
    {
        global $DB;

        $dbu = new DbUtils();
        $grouplinktable = $dbu->getTableForItemType($target->obj->grouplinkclass);
        $fkfield = $target->obj->getForeignKeyField();

        $last = [
            'SELECT' => ['MAX' => 'id AS lastid'],
            'FROM' => $grouplinktable,
            'WHERE' => [
                $grouplinktable . '.' . $fkfield => $target->obj->fields["id"],
                $grouplinktable . '.type' => $type,
            ],
        ];
        $result = $DB->request($last);

        //Look for the user by his id
        $query = [
            'SELECT' => 'groups_id',
            'FROM' => $grouplinktable,
            'WHERE' => [
                $grouplinktable . '.' . $fkfield => $target->obj->fields["id"],
                $grouplinktable . '.type' => $type,
            ],
        ];

        if ($data = $result->current()) {
            $object = new $target->obj->grouplinkclass();
            if ($object->getFromDB($data['lastid'])) {
                $query['WHERE']['groups_id'] = $object->fields['groups_id'];
            }
        }
        foreach ($DB->request($query) as $data) {
            //Add the group in the notified users list
            self::addForGroup($supervisor, $object->fields['groups_id'], $target);
        }
    }


    /**
     * @param $target
     * @return void
     */
    public static function getLastSupplierAddress($target)
    {
        global $DB;

        if (!$target->options['sendprivate']
            && $target->obj->countSuppliers(CommonITILActor::ASSIGN)) {
            $dbu = new DbUtils();
            $supplierlinktable = $dbu->getTableForItemType($target->obj->supplierlinkclass);
            $fkfield = $target->obj->getForeignKeyField();

            $last = [
                'SELECT' => ['MAX' => 'id AS lastid'],
                'FROM' => $supplierlinktable,
                'WHERE' => [$supplierlinktable . '.' . $fkfield => $target->obj->fields["id"]],
            ];

            $result = $DB->request($last);
            $data = $result->current();

            $query = [
                'SELECT' => ['glpi_suppliers.email AS email','glpi_suppliers.name AS name'],
                'DISTINCT' => true,
                'FROM' => $supplierlinktable,
                'LEFT JOIN' => [
                    'glpi_suppliers'
                    => [
                        'FKEY' => [
                            $supplierlinktable => 'suppliers_id',
                            'glpi_suppliers' => 'id',
                        ],
                    ],
                ],
                'WHERE' => [$supplierlinktable . '.' . $fkfield => $target->obj->getID()],
            ];

            $object = new $target->obj->supplierlinkclass();
            if ($object->getFromDB($data['lastid'])) {
                $query['WHERE'][$supplierlinktable . '.suppliers_id'] = $object->fields['suppliers_id'];
            }
            foreach ($DB->request($query) as $data) {
                $target->addToRecipientsList($data);
            }
        }
    }


    /**
     * @param Ticket $ticket
     * @return false|void
     */
    public static function beforeAdd(\Ticket $ticket)
    {
        global $DB;

        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = Config::getInstance();

        if ($config->getField('tickets_id_format')) {
            $max = 0;
            $sql = [
                'SELECT' => ['MAX' => 'id AS max'],
                'FROM' => 'glpi_tickets',
            ];
            foreach ($DB->request($sql) as $data) {
                $max = $data['max'];
            }
            $want = date($config->getField('tickets_id_format'));
            if ($max < $want) {
                $DB->doQuery("ALTER TABLE `glpi_tickets` AUTO_INCREMENT=$want");
            }
        }

        if ($config->getField('use_requester_user_group') > 0) {
            if (!isset($ticket->input['_groups_id_requester'])
                || $ticket->input['_groups_id_requester'] == 0) {
                $requesters = self::useRequesterUserGroup($ticket->input);
                if (isset($ticket->input['_actors']['requester'])) {
                    $ticket->input['_actors']['requester'] = array_merge(
                        $ticket->input['_actors']['requester'],
                        $requesters
                    );
                } else {
                    if ($requesters !== null) {
                        $ticket->input['_actors']['requester'] = $requesters;
                    }
                }
            }
        }

        if ($config->getField('use_assign_user_group') > 0) {
            if (!isset($ticket->input['_groups_id_assign'])
                || $ticket->input['_groups_id_assign'] == 0) {
                $assigns = self::useAssignTechGroup($ticket->input, 'use_assign_user_group');
                if ($assigns !== null) {
                    $ticket->input['_actors']['assign'] = self::removeDuplicates($assigns);
                }
            }
        }

        if ($config->getField('ticketsolved_updatetech')
            && (isset($ticket->input['status'])
                && in_array(
                    $ticket->input['status'],
                    array_merge(
                        \Ticket::getSolvedStatusArray(),
                        \Ticket::getClosedStatusArray()
                    )
                ))
            && isset($ticket->input['_users_id_assign'])
            && (($ticket->input['_users_id_assign'] == 0)
                || ($ticket->input['_users_id_assign'] != Session::getLoginUserID()))) {
            $ticket->input['_users_id_assign'] = Session::getLoginUserID();
        }
    }

    /**
     * @param $array
     * @return array|void
     */
    public static function removeDuplicates($array)
    {
        $unique = [];
        $filteredArray = [];
        if (is_array($array)) {
            foreach ($array as $item) {
                // Clé unique basée sur itemtype + items_id
                $key = $item['itemtype'] . '-' . $item['items_id'];

                if (!isset($unique[$key])) {
                    $unique[$key] = true;
                    $filteredArray[] = $item;
                }
            }
            return $filteredArray;
        }
    }

    /**
     * @param $input
     * @return array|mixed|void
     */
    public static function useRequesterItemGroup($input)
    {
        $config = Config::getInstance();
        if ($config->getField('use_requester_item_group')
            && isset($input['items_id'])
            && (is_array($input['items_id']))) {
            foreach ($input['items_id'] as $type => $items) {
                if (($item = getItemForItemtype($type))
                    && isset($input['_actors'])) {
                    $actors_item = $input['_actors'];

                    //for simplified interface
                    if (!isset($actors_item['requester']) && isset($input['_users_id_requester'])) {
                        if (is_array($input['_users_id_requester'])) {
                            foreach ($input['_users_id_requester'] as $usr) {
                                $actors_item['requester'][] = [
                                    'itemtype' => 'User',
                                    'items_id' => $usr,
                                    'use_notification' => "1",
                                    'alternative_email' => "",
                                ];
                            }
                        } else {
                            $actors_item['requester'][] = [
                                'itemtype' => 'User',
                                'items_id' => $input['_users_id_requester'],
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    }

                    if (isset($actors_item['requester'])) {
                        $requesters = $actors_item['requester'];
                        $ko = 0;
                        foreach ($requesters as $requester) {
                            if ($requester['itemtype'] == 'Group') {
                                $ko++;
                            }
                        }
                        if ($ko == 0 && $item->isField('groups_id')) {
                            foreach ($items as $itemid) {
                                if ($item->getFromDB($itemid)) {
                                    $actors_item['requester'][] = [
                                        'itemtype' => 'Group',
                                        'items_id' => $item->getField('groups_id'),
                                        'use_notification' => "1",
                                        'alternative_email' => "",
                                    ];
                                }
                            }
                        }

                        return $actors_item;
                    }
                }
            }
        }
    }

    /**
     * @param $input
     * @return array|mixed|void
     */
    public static function useRequesterUserGroup($input)
    {
        $config = Config::getInstance();
        if ($config->getField('use_requester_user_group') > 0) {
            $actors_requester = [];
            if (isset($input['_actors']['requester'])) {
                $actors_requester = $input['_actors']['requester'];
            }

            if (isset($ticket->input['_mailgate']) && $ticket->input['_mailgate'] > 0) {
                if (isset($ticket->input['_users_id_requester_notif']['alternative_email'][0])) {
                    $email = $ticket->input['_users_id_requester_notif']['alternative_email'][0];
                    $condition = [
                        'glpi_users.is_active'  => 1,
                        'glpi_users.is_deleted' => 0, [
                            'OR' => [
                                ['glpi_users.begin_date' => null],
                                ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]],
                            ],
                        ], [
                            'OR'  => [
                                ['glpi_users.end_date'   => null],
                                ['glpi_users.end_date'   => ['>', new QueryExpression('NOW()')]],
                            ],
                        ],
                    ];
                    $user = new \User();
                    if ($user->getFromDBbyEmail($email, $condition)) {
                        $input['_users_id_requester'] = $user->getID;
                    } else {
                        return $input;
                    }
                }
            }

            //for simplified interface or mailgate
            if (count($actors_requester) == 0
                && isset($input['_users_id_requester'])) {
                if (is_array($input['_users_id_requester'])) {
                    foreach ($input['_users_id_requester'] as $usr) {
                        if ($usr > 0) {
                            $actors_requester[] = [
                                'itemtype' => 'User',
                                'items_id' => $usr,
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    }
                } else {
                    //Case of anonymous user
                    if ($input['_users_id_requester'] > 0) {
                        $actors_requester[] = [
                            'itemtype' => 'User',
                            'items_id' => $input['_users_id_requester'],
                            'use_notification' => "1",
                            'alternative_email' => "",
                        ];
                    }
                }
            }
            if (isset($input['_groups_id_requester'])) {
                if (is_array($input['_groups_id_requester'])) {
                    foreach ($input['_groups_id_requester'] as $grp) {
                        if ($grp > 0) {
                            $actors_requester[] = [
                                'itemtype' => 'Group',
                                'items_id' => $grp,
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    }
                } else {
                    //Case of anonymous user
                    if ($input['_groups_id_requester'] > 0) {
                        $actors_requester[] = [
                            'itemtype' => 'Group',
                            'items_id' => $input['_groups_id_requester'],
                            'use_notification' => "1",
                            'alternative_email' => "",
                        ];
                    }
                }
            }
            $entities_id = $_SESSION['glpiactive_entity'];
            if (!isset($input['entities_id'])) {
                $ticket = new \Ticket();
                if ($ticket->getFromDB($input['id'])) {
                    $entities_id = $ticket->fields['entities_id'];
                }
            } else {
                $entities_id = $input['entities_id'];
            }

            if (count($actors_requester) > 0) {
                $requesters = $actors_requester;
                // Select first group of this user
                $ko = 0;
                $grp = 0;
                $grps = [];
                foreach ($requesters as $requester) {
                    if ($config->getField('use_requester_user_group') == 1) {
                        // First group
                        if ($requester['itemtype'] == 'User') {
                            $grp = User::getRequesterGroup(
                                $entities_id,
                                $requester['items_id'],
                                true
                            );
                        }
                        if ($grp > 0 && $requester['itemtype'] == 'Group'
                            && $requester['items_id'] == $grp) {
                            $ko++;
                        }
                        if ($grp > 0 && $ko == 0) {
                            $actors_requester[] = [
                                'itemtype' => 'Group',
                                'items_id' => $grp,
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    } else {
                        // All groups
                        if ($requester['itemtype'] == 'User') {
                            $grps = User::getRequesterGroup(
                                $entities_id,
                                $requester['items_id'],
                                false
                            );
                        }
                        if ($requester['itemtype'] == 'Group'
                            && in_array($requester['items_id'], $grps)) {
                            unset($grps[$requester['items_id']]);
                        }
                        if (count($grps) > 0) {
                            foreach ($grps as $grp) {
                                $actors_requester[] = [
                                    'itemtype' => 'Group',
                                    'items_id' => $grp,
                                    'use_notification' => "1",
                                    'alternative_email' => "",
                                ];
                            }
                        }
                    }
                }
                return $actors_requester;
            }
        }
    }

    /**
     * @param $input
     * @return array|mixed|void
     */
    public static function useAssignTechGroup($input, $type)
    {
        $config = Config::getInstance();

        if ($config->getField($type) > 0) {
            $actors_assign = [];
            if (isset($input['_actors']['assign'])) {
                $actors_assign = $input['_actors']['assign'];
            }

            //for simplified interface or mailgate
            if (count($actors_assign) == 0
                && isset($input['_users_id_assign'])) {
                if (is_array($input['_users_id_assign'])) {
                    foreach ($input['_users_id_assign'] as $usr) {
                        if ($usr > 0) {
                            $actors_assign[] = [
                                'itemtype' => 'User',
                                'items_id' => $usr,
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    }
                } else {
                    //Case of anonymous user
                    if ($input['_users_id_assign'] > 0) {
                        $actors_assign[] = [
                            'itemtype' => 'User',
                            'items_id' => $input['_users_id_assign'],
                            'use_notification' => "1",
                            'alternative_email' => "",
                        ];
                    }
                }
            }
            if (isset($input['_groups_id_assign'])) {
                if (is_array($input['_groups_id_assign'])) {
                    foreach ($input['_groups_id_assign'] as $grp) {
                        if ($grp > 0) {
                            $actors_assign[] = [
                                'itemtype' => 'Group',
                                'items_id' => $grp,
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    }
                } else {
                    //Case of anonymous user
                    if ($input['_groups_id_assign'] > 0) {
                        $actors_assign[] = [
                            'itemtype' => 'Group',
                            'items_id' => $input['_groups_id_assign'],
                            'use_notification' => "1",
                            'alternative_email' => "",
                        ];
                    }
                }
            }

            $entities_id = $_SESSION['glpiactive_entity'];
            if (!isset($input['entities_id'])) {
                $ticket = new \Ticket();
                if ($ticket->getFromDB($input['id'])) {
                    $entities_id = $ticket->fields['entities_id'];
                }
            } else {
                $entities_id = $input['entities_id'];
            }

            if (count($actors_assign) > 0) {
                $assigns = $actors_assign;
                // Select first group of this user
                $ko = 0;
                $grp = 0;
                $grps = [];
                foreach ($assigns as $assign) {
                    if ($config->getField($type) == 1) {
                        // First group
                        if ($assign['itemtype'] == 'User') {
                            $grp = User::getTechnicianGroup(
                                $entities_id,
                                $assign['items_id'],
                                true
                            );
                        }
//                        if ($grp > 0 && $assign['itemtype'] == 'Group'
//                            && $assign['items_id'] == $grp) {
//                            $ko++;
//                        }
                        if ($grp > 0 && $ko == 0) {
                            $actors_assign[] = [
                                'itemtype' => 'Group',
                                'items_id' => $grp,
                                'use_notification' => "1",
                                'alternative_email' => "",
                            ];
                        }
                    } else {
                        // All groups
                        if ($assign['itemtype'] == 'User') {
                            $grps = User::getTechnicianGroup(
                                $entities_id,
                                $assign['items_id'],
                                false
                            );
                        }
                        if ($assign['itemtype'] == 'Group'
                            && in_array($assign['items_id'], $grps)) {
                            unset($grps[$assign['items_id']]);
                        }
                        if (count($grps) > 0) {
                            foreach ($grps as $grp) {
                                $actors_assign[] = [
                                    'itemtype' => 'Group',
                                    'items_id' => $grp,
                                    'use_notification' => "1",
                                    'alternative_email' => "",
                                ];
                            }
                        }
                    }
                }
                return $actors_assign;
            }
        }
    }

    /**
     * @param Ticket $ticket
     * @return false|void
     */
    public static function afterPrepareAdd(\Ticket $ticket)
    {
        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        $config = Config::getInstance();

        if ($config->getField('use_assign_user_group')
            && isset($ticket->input['_users_id_assign'])
            && ($ticket->input['_users_id_assign'] > 0)
            && (!isset($ticket->input['_groups_id_assign'])
                || ($ticket->input['_groups_id_assign'] <= 0))) {
            if ($config->getField('use_assign_user_group') == 1) {
                // First group
                $ticket->input['_groups_id_assign']
                    = User::getTechnicianGroup(
                        $ticket->input['entities_id'],
                        $ticket->input['_users_id_assign'],
                        true
                    );
            } else {
                // All groups
                $ticket->input['_additional_groups_assigns']
                    = User::getTechnicianGroup(
                        $ticket->input['entities_id'],
                        $ticket->input['_users_id_assign'],
                        false
                    );
            }
        }
    }


    /**
     * @param Ticket $ticket
     * @return false|void
     */
    public static function beforeUpdate(\Ticket $ticket)
    {
        global $DB;

        if (!is_array($ticket->input) || !count($ticket->input)) {
            // Already cancel by another plugin
            return false;
        }

        $dbu = new DbUtils();
        $config = Config::getInstance();

        // Check is the connected user is a tech
        if (!is_numeric(Session::getLoginUserID(false))
            || !Session::haveRight('ticket', UPDATE)) {
            return false; // No check
        }

        if (isset($ticket->input['date'])) {
            if ($config->getField('is_ticketdate_locked')) {
                unset($ticket->input['date']);
            }
        }

        if (isset($ticket->input['status'])
            && in_array(
                $ticket->input['status'],
                array_merge(
                    \Ticket::getSolvedStatusArray(),
                    \Ticket::getClosedStatusArray()
                )
            )) {
            $sql = [
                'SELECT' => [
                    'MAX' => 'id AS max',
                    'solutiontypes_id',
                    'content',
                ],
                'FROM' => 'glpi_itilsolutions',
                'WHERE' => [
                    'items_id' => $ticket->getID(),
                    'itemtype' => 'Ticket',
                ],
            ];

            foreach ($DB->request($sql) as $data) {
                if ($config->getField('is_ticketsolutiontype_mandatory')
                    && ($data['solutiontypes_id'] == 0)) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Type of solution is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
                if ($config->getField('is_ticketsolution_mandatory')
                    && empty($data['content'])) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Description of solution is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
            }

            $dur = ($ticket->input['actiontime']
                ?? $ticket->fields['actiontime']);
            $cat = ($ticket->input['itilcategories_id']
                ?? $ticket->fields['itilcategories_id']);
            $loc = ($ticket->input['locations_id']
                ?? $ticket->fields['locations_id']);

            // Wand to solve/close the ticket
            if ($config->getField('is_ticketrealtime_mandatory')) {
                if (!$dur) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Duration is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
            }
            if ($config->getField('is_ticketcategory_mandatory')) {
                if (!$cat) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Category is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
            }
            if ($config->getField('is_tickettech_mandatory')) {
                if (($ticket->countUsers(CommonITILActor::ASSIGN) == 0)
                    && !$config->getField('ticketsolved_updatetech')) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Technician assigned is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
            }
            if ($config->getField('is_tickettechgroup_mandatory')) {
                if (($ticket->countGroups(CommonITILActor::ASSIGN) == 0)) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Group of technicians assigned is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
            }
            if ($config->getField('is_ticketlocation_mandatory')) {
                if (!$loc) {
                    unset($ticket->input['status']);
                    Session::addMessageAfterRedirect(
                        __(
                            "Location is mandatory before ticket is solved/closed",
                            'behaviors'
                        ),
                        true,
                        ERROR
                    );
                }
            }
            if ($config->getField('is_tickettasktodo')) {

                $crit = [
                    'FROM' => 'glpi_tickettasks',
                    'WHERE' => [
                        'tickets_id' => $ticket->getField('id')
                    ]
                ];

                foreach (
                    $DB->request($crit) as $task
                ) {
                    if ($task['state'] == 1) {
                        Session::addMessageAfterRedirect(
                            __(
                                "You cannot solve/close a ticket with task do to",
                                'behaviors'
                            ),
                            true,
                            ERROR
                        );
                        unset($ticket->input['status']);
                    }
                }
            }
        }
        $cat = ($ticket->input['itilcategories_id']
            ?? $ticket->fields['itilcategories_id']);

        if ($config->getField('is_ticketcategory_mandatory_on_assign')) {
            if (!$cat
                && isset($ticket->input['_actors']['assign'])) {
                $ticket->input = [];
                Session::addMessageAfterRedirect(
                    __(
                        "Category is mandatory when you assign a ticket",
                        'behaviors'
                    ),
                    true,
                    ERROR
                );
            }
        }

//        if ($config->getField('use_requester_item_group')
//            && isset($ticket->input['_actors']['requester'])) {
//            $requesters = self::useRequesterItemGroup($ticket->input);
//            if ($requesters !== null) {
//                $ticket->input['_actors']['requester'] = self::removeDuplicates($requesters);
//            }
//        }

        if ($config->getField('use_assign_user_group_update')
            && isset($ticket->input['_actors']['assign'])) {
            $assigns = self::useAssignTechGroup($ticket->input, 'use_assign_user_group_update');
            if ($assigns !== null) {
                $ticket->input['_actors']['assign'] = self::removeDuplicates($assigns);
            }
        }

        if ($config->getField('ticketsolved_updatetech')
            && $ticket->canUpdate()
            && isset($ticket->input['status'])
            && in_array(
                $ticket->input['status'],
                array_merge(
                    \Ticket::getSolvedStatusArray(),
                    \Ticket::getClosedStatusArray()
                )
            )) {
            $ticket_user = new \Ticket_User();
            if (($ticket->countUsers(CommonITILActor::ASSIGN) == 0)
                || (isset($ticket_user->fields['users_id'])
                    && ($ticket_user->fields['users_id'] != Session::getLoginUserID()))
                && (((in_array($ticket->fields['status'], \Ticket::getSolvedStatusArray()))
                        && (in_array($ticket->input['status'], \Ticket::getClosedStatusArray())))
                    || !in_array(
                        $ticket->fields['status'],
                        array_merge(
                            \Ticket::getSolvedStatusArray(),
                            \Ticket::getClosedStatusArray()
                        )
                    ))) {
                $ticket_user->add([
                    'tickets_id' => $ticket->getID(),
                    'users_id' => Session::getLoginUserID(),
                    'type' => CommonITILActor::ASSIGN,
                ]);
            }
        }
    }


    /**
     * @return void
     */
    public static function onNewTicket()
    {
        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && ($_SESSION['glpiactiveprofile']['interface'] == 'central')) {
            if (strstr($_SERVER['PHP_SELF'], "/front/ticket.form.php")
                && (!isset($_POST['id']) || ($_POST['id'] == 0))) {
                $config = Config::getInstance();

                if ($config->getField('use_requester_user_group') > 0
                    && isset($_POST['_actors'])) {
                    $actors = json_decode($_POST['_actors'], true);
                    if (isset($actors['requester'])) {
                        $requesters = $actors['requester'];
                        // Select first group of this user
                        $group_requester_actors = [];
                        foreach ($requesters as $requester) {
                            if ($requester['itemtype'] == 'Group') {
                                $group_requester_actors[] = $requester['items_id'];
                            }
                            if ($requester['itemtype'] == 'User') {
                                if ($config->getField('use_requester_user_group') == 1) {
                                    // First group
                                    $grp = User::getRequesterGroup(
                                        $_POST['entities_id'],
                                        $requester['items_id'],
                                        true
                                    );
                                    if ($grp > 0 && !isset($_SESSION['glpi_behaviors_auto_group_request'])
                                        || (isset($_SESSION['glpi_behaviors_auto_group_request'])
                                            && is_array($_SESSION['glpi_behaviors_auto_group_request'])
                                            && !in_array($grp, $_SESSION['glpi_behaviors_auto_group_request']))
                                        && !in_array($grp, $group_requester_actors)) {
                                        $actors['requester'][] = [
                                            'itemtype' => 'Group',
                                            'items_id' => $grp,
                                            'use_notification' => "1",
                                            'alternative_email' => "",
                                        ];
                                        $_SESSION['glpi_behaviors_auto_group_request'][] = $grp;
                                    }
                                    $new_actors = json_encode($actors);
                                    $_POST['_actors'] = $new_actors;
                                } else {
                                    // All groups
                                    $grps = User::getRequesterGroup(
                                        $_POST['entities_id'],
                                        $requester['items_id'],
                                        false
                                    );
                                    foreach ($grps as $grp) {
                                        if (!isset($_SESSION['glpi_behaviors_auto_group_request'])
                                            || (isset($_SESSION['glpi_behaviors_auto_group_request'])
                                                && is_array($_SESSION['glpi_behaviors_auto_group_request'])
                                                && !in_array($grp, $_SESSION['glpi_behaviors_auto_group_request']))
                                            && !in_array($grp, $group_requester_actors)) {
                                            $actors['requester'][] = [
                                                'itemtype' => 'Group',
                                                'items_id' => $grp,
                                                'use_notification' => "1",
                                                'alternative_email' => "",
                                            ];
                                            $_SESSION['glpi_behaviors_auto_group_request'][] = $grp;
                                        }
                                    }
                                }
                                $new_actors = json_encode($actors);
                                $_POST['_actors'] = $new_actors;
                            }
                        }
                    }
                } else {
                    unset($_SESSION['glpi_behaviors_auto_group_request']);
                }

                if ($config->getField('use_assign_user_group') > 0
                    && isset($_POST['_actors'])) {
                    $actors = json_decode($_POST['_actors'], true);
                    if (isset($actors['assign'])) {
                        $assigneds = $actors['assign'];
                        // Select first group of this user
                        $group_assign_actors = [];
                        foreach ($assigneds as $assigned) {
                            if ($assigned['itemtype'] == 'Group') {
                                $group_assign_actors[] = $assigned['items_id'];
                            }
                            if ($assigned['itemtype'] == 'User') {
                                if ($config->getField('use_assign_user_group') == 1) {
                                    // First group
                                    $grp = User::getTechnicianGroup(
                                        $_POST['entities_id'],
                                        $assigned['items_id'],
                                        true
                                    );
                                    if ($grp > 0 && !isset($_SESSION['glpi_behaviors_auto_group_assign'])
                                        || (isset($_SESSION['glpi_behaviors_auto_group_assign'])
                                            && is_array($_SESSION['glpi_behaviors_auto_group_assign'])
                                            && !in_array($grp, $_SESSION['glpi_behaviors_auto_group_assign']))
                                        && !in_array($grp, $group_assign_actors)) {
                                        $actors['assign'][] = [
                                            'itemtype' => 'Group',
                                            'items_id' => $grp,
                                            'use_notification' => "1",
                                            'alternative_email' => "",
                                        ];
                                        $_SESSION['glpi_behaviors_auto_group_assign'][] = $grp;
                                    }
                                    $new_actors = json_encode($actors);
                                    $_POST['_actors'] = $new_actors;
                                } else {
                                    // All groups
                                    $grps = User::getTechnicianGroup(
                                        $_POST['entities_id'],
                                        $assigned['items_id'],
                                        false
                                    );
                                    foreach ($grps as $grp) {
                                        if (!isset($_SESSION['glpi_behaviors_auto_group_assign'])
                                            || (isset($_SESSION['glpi_behaviors_auto_group_assign'])
                                                && is_array($_SESSION['glpi_behaviors_auto_group_assign'])
                                                && !in_array($grp, $_SESSION['glpi_behaviors_auto_group_assign']))
                                            && !in_array($grp, $group_assign_actors)) {
                                            $actors['assign'][] = [
                                                'itemtype' => 'Group',
                                                'items_id' => $grp,
                                                'use_notification' => "1",
                                                'alternative_email' => "",
                                            ];
                                            $_SESSION['glpi_behaviors_auto_group_assign'][] = $grp;
                                        }
                                    }
                                }
                                $new_actors = json_encode($actors);
                                $_POST['_actors'] = $new_actors;
                            }
                        }
                    }
                } else {
                    unset($_SESSION['glpi_behaviors_auto_group_assign']);
                }
            } elseif (strstr($_SERVER['PHP_SELF'], "/front/ticket.form.php")) {
                unset($_SESSION['glpi_behaviors_auto_group_request']);
                unset($_SESSION['glpi_behaviors_auto_group_assign']);
            }
        }
    }


    /**
     * @param Ticket $ticket
     * @return void
     */
    public static function afterUpdate(\Ticket $ticket)
    {
        $config = Config::getInstance();

        if ($config->getField('add_notif')
            && in_array('status', $ticket->updates)) {
            if (in_array(
                $ticket->oldvalues['status'],
                array_merge(
                    \Ticket::getSolvedStatusArray(),
                    \Ticket::getClosedStatusArray()
                )
            )
                && !in_array(
                    $ticket->input['status'],
                    array_merge(
                        \Ticket::getSolvedStatusArray(),
                        \Ticket::getClosedStatusArray()
                    )
                )) {
                NotificationEvent::raiseEvent('plugin_behaviors_ticketreopen', $ticket);
            } elseif ($ticket->oldvalues['status'] <> $ticket->input['status']) {
                if ($ticket->input['status'] == CommonITILObject::WAITING) {
                    NotificationEvent::raiseEvent('plugin_behaviors_ticketwaiting', $ticket);
                } else {
                    NotificationEvent::raiseEvent('plugin_behaviors_ticketstatus', $ticket);
                }
            }
        }
    }


    /**
     * @param Ticket $srce
     * @param array $input
     * @return array
     */
    public static function preClone(\Ticket $srce, array $input)
    {
        global $DB;

        $user_reques = $srce->getUsers(CommonITILActor::REQUESTER);
        $input['_users_id_requester'] = [];
        foreach ($user_reques as $users) {
            $input['_users_id_requester'][] = $users['users_id'];
        }
        $user_observ = $srce->getUsers(CommonITILActor::OBSERVER);
        $input['_users_id_observer'] = [];
        foreach ($user_observ as $users) {
            $input['_users_id_observer'][] = $users['users_id'];
        }
        $user_assign = $srce->getUsers(CommonITILActor::ASSIGN);
        $input['_users_id_assign'] = [];
        foreach ($user_assign as $users) {
            $input['_users_id_assign'][] = $users['users_id'];
        }
        $group_reques = $srce->getGroups(CommonITILActor::REQUESTER);
        $input['_groups_id_requester'] = [];
        foreach ($group_reques as $groups) {
            $input['_groups_id_requester'][] = $groups['groups_id'];
        }
        $group_observ = $srce->getGroups(CommonITILActor::OBSERVER);
        $input['_groups_id_observer'] = [];
        foreach ($group_observ as $groups) {
            $input['_groups_id_observer'][] = $groups['groups_id'];
        }
        $group_assign = $srce->getGroups(CommonITILActor::ASSIGN);
        $input['_groups_id_assign'] = [];
        foreach ($group_assign as $groups) {
            $input['_groups_id_assign'][] = $groups['groups_id'];
        }

        $suppliers = $srce->getSuppliers(CommonITILActor::ASSIGN);
        $input['_suppliers_id_assign'] = [];
        foreach ($suppliers as $supplier) {
            $input['_suppliers_id_assign'][] = $supplier['groups_id'];
        }

        return $input;
    }


    /**
     * @param Ticket $clone
     * @param $oldid
     * @return void
     */
    public static function postClone(\Ticket $clone, $oldid)
    {
        global $DB;

        $dbu = new DbUtils();
        $fkey = $dbu->getForeignKeyFieldForTable($clone->getTable());
        // add items of tickets source
        $item = new Item_Ticket();
        $crit = [
            'FROM' => $item->getTable(),
            'WHERE' => [
                $fkey => $oldid
            ]
        ];
        foreach ($DB->request($crit) as $dataitem) {
            $input = [
                'itemtype' => $dataitem['itemtype'],
                'items_id' => $dataitem['items_id'],
                'tickets_id' => $clone->getField('id'),
            ];
            $item->add($input);
        }

        // link new ticket to ticket source
        $link = new Ticket_Ticket();
        $inputlink = [
            'tickets_id_1' => $clone->getField('id'),
            'tickets_id_2' => $oldid,
            'link' => 1,
        ];
        $link->add($inputlink);

        if ($dbu->countElementsInTable(
            "glpi_documents_items",
            [
                'itemtype' => 'Ticket',
                'items_id' => $oldid,
            ]
        )) {
            $docitem = new \Document_Item();
            $query = [
                'FROM' => 'glpi_documents_items',
                'WHERE' => [
                    'itemtype' => 'Ticket',
                    'items_id' => $oldid,
                ]
            ];
            foreach (
                $DB->request($query) as $doc
            ) {
                $inputdoc = [
                    'documents_id' => $doc['documents_id'],
                    'items_id' => $clone->getField('id'),
                    'itemtype' => 'Ticket',
                    'entities_id' => $doc['entities_id'],
                    'is_recursive' => $doc['is_recursive'],
                ];
                $docitem->add($inputdoc);
            }
        }
    }


    /**
     * @param $manager
     * @param $group_id
     * @param $target
     * @return void
     */
    public static function addForGroup($manager, $group_id, $target)
    {
        global $DB;

        $dbu = new DbUtils();

        // members/managers of the group allowed on object entity
        // filter group with 'is_assign' (attribute can be unset after notification)
        $criteria = [
            'SELECT' => ['glpi_users.id AS users_id',
                'glpi_users.language AS language'],
            'DISTINCT'        => true,
            'FROM' => 'glpi_groups_users',
            'INNER JOIN' => [
                'glpi_users' => [
                    'ON' => [
                        'glpi_groups_users' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ],
                'glpi_profiles_users' => [
                    'ON' => [
                        'glpi_profiles_users' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ],
                'glpi_groups' => [
                    'ON' => [
                        'glpi_groups_users' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ],
            ],
            'WHERE' => [
                'glpi_groups_users'.'.groups_id' => $group_id,
                'glpi_groups'.'.is_notify' => 1,
            ]
        ];
        if ($manager == 1) {
            $criteria['WHERE'] = $criteria['WHERE'] +  ['glpi_groups_users.is_manager' => 1];
        } elseif ($manager == 2) {
            $criteria['WHERE'] = $criteria['WHERE'] +  ['NOT'       => ['glpi_groups_users.is_manager' => null]];
        }

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_profiles_users', 'entities_id', $target->getEntity(), true
            );

        foreach ($DB->request($criteria) as $data) {
            $target->addToRecipientsList($data);
        }
    }
}
