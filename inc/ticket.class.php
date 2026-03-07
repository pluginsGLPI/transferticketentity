<?php
/**
 * -------------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Transferticketentity plugin for GLPI.
 *
 * Transferticketentity is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Transferticketentity is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Reports. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Ticket
 * @package   Transferticketentity
 * @author    Yannick Comba <y.comba@maine-et-loire.fr>
 * @copyright 2015-2023 Département de Maine et Loire plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/departement-maine-et-loire/
 * --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginTransferticketentityTicket extends CommonDBTM
{
    public static $rightname = "plugin_transferticketentity_use";

    /**
     * Get all the entities which aren't the current entity with their rights
     *
     * @return array
     */
    public function getEntitiesRights($entities_id)
    {
        $array = [];
        $entity_config = new PluginTransferticketentityEntity();
        $entities = $entity_config->find(['NOT' => ['entities_id' => $entities_id]]);

        if (count($entities) > 0) {
            foreach ($entities as $data) {
                $temp_array['id'] = $data['id'];
                $temp_array['entities_id'] = $data['entities_id'];
                $entity = new Entity();
                $entity->getFromDB($data['entities_id']);
                $temp_array['name'] = $entity->getName();
                $temp_array['allow_entity_only_transfer'] = $data['allow_entity_only_transfer'];
                $temp_array['justification_transfer'] = $data['justification_transfer'];
                $temp_array['allow_transfer'] = $data['allow_transfer'];
                array_push($array, $temp_array);
            }
        }


        return $array;
    }

    /**
     * Get the groups to which tickets can be assigned
     *
     * @return array $allGroupsEntities
     */
    public function getGroupEntities($entities)
    {
        global $DB;

        $array = [];

        $result = $DB->request([
            'FROM' => 'glpi_groups',
            'WHERE' => ['is_assign' => 1, 'entities_id' => $entities],
            'ORDER' => ['entities_id ASC', 'id ASC'],
        ]);

        $array = [];

        foreach ($result as $data) {
//            array_push(
//                $array,
//                $data['id'],
//                $data['entities_id'],
//                $data['name']
//            );
            $array[$data['id']] = $data['name'];
        }

        return $array;
    }


    /**
     * Return parent's entity name
     *
     * @return string
     */
    public function searchParentEntityName($id)
    {
        global $DB;

        $result = $DB->request([
            'FROM' => 'glpi_entities',
        ]);

        foreach ($result as $subArray) {
            if ($subArray['id'] == $id) {
                return $subArray['completename'];
            }
        }
    }

    /**
     * Display the ticket transfer form
     *
     * @return void
     */
    public function showFormMcv($ticket)
    {
        $getEntitiesRights = self::getEntitiesRights($ticket->fields['entities_id']);

        $getGroupEntities = [];

        $technician_profile = $_SESSION['glpiactiveprofile']['id'];


        foreach ($getEntitiesRights as $entity) {
            if ($entity['allow_transfer'] == 1) {
                $groups = self::getGroupEntities($entity['entities_id']);
                $getGroupEntities[] = $groups;
            }
        }

//        Toolbox::logInfo($getGroupEntities);
        if (!Session::haveRight('ticket', UPDATE)) {
            echo "<div class='unauthorised'>";
            echo "<p>" .
                __("You don't have right to update tickets. Please contact your administrator.", "transferticketentity")
                . "</p>";
            echo "</div>";

            return false;
        }

        if (count($getEntitiesRights) == 0) {
            echo "<div class='group_not_found'>";
            echo "<p>" .
                __("No entity available found, transfer impossible.", "transferticketentity")
                . "</p>";
            echo "</div>";

            return false;
        }

        // Check if ticket is closed
        if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
            echo "<div class='unauthorised'>";
            echo "<p>" .
                __("Unauthorized transfer on closed ticket.", "transferticketentity")
                . "</p>";
            echo "</div>";

            return false;
        }
//
//        $theServer = explode("front/profile.form.php?", $_SERVER["HTTP_REFERER"]);
//        $theServer = $theServer[0];

        $id_ticket = $ticket->getID();

        $id_user = $_SESSION["glpiID"];

        // In case JS is not functionnal
//        echo "<div id='tt_gest_error'>";
//        echo "<span class='loader'></span>";
//        echo "</div>";

        $previousEntity = null;

        echo "
            <form  action='" . $this->getFormURL() . "' method='post'>
                <div class='tt_entity_choice'>
                    <label for='entity_choice'>" . __("Select ticket entity to transfer", "transferticketentity") . " : </label>
                    <select name='entity_choice' id='entity_choice' style='width: 30%'>
                        <option selected disabled value=''>-- " . __(
                        "Choose your entity",
                        "transferticketentity"
                    ) . " --</option>";

        foreach ($getEntitiesRights as $entity) {
            if ($entity['allow_transfer']) {
                if ($entity['entities_id'] === null) {
                    echo "<optgroup label='" . __('No previous entity', 'transferticketentity') . "'>";
                    echo "<option value='" . $entity['id'] . "'>" . $entity['name'] . "</option>";
                } else {
                    $searchParentEntityName = self::searchParentEntityName($entity['entities_id']);
                    if ($previousEntity != $searchParentEntityName) {
                        echo "</optgroup>";
                        echo "<optgroup label='" . $searchParentEntityName . "'>";
                    }

                    echo "<option value='" . $entity['entities_id'] . "'>" . $entity['name'] . "</option>";
                    $previousEntity = $searchParentEntityName;
                }
            }
        }
        echo "</optgroup>
                    </select>
                </div>";

        echo " <div class='group_not_found' id='nogroupfound' style='display: none'>" .
            __(
                "No group found with « Assigned to » right while a group is required. Transfer impossible.",
                "transferticketentity"
            )
            . "</div>";

        echo " <div class='tt_flex'>
                    <div class='tt_group_choice'>
                        <label for='group_choice'>" . __("Select the group to assign", "transferticketentity") . " : </label>
                        <select name='group_choice' id='group_choice' style='width: 30%'>
                            <option id='no_select' disabled value=''>-- " . __(
                            "Choose your group",
                            "transferticketentity"
                        ) . " --</option>
                            <option value='' id='tt_none'> " . __("None", "transferticketentity") . " </option>";
//        for ($i = 0; $i < count($getGroupEntities); $i = $i + 3) {
//            echo "<option class='tt_plugin_entity_" . $getGroupEntities[$i + 1] . "' value='" . $getGroupEntities[$i] . "'>" . $getGroupEntities[$i + 2] . "</option>";
//        }

        foreach ($getGroupEntities as $k => $groups) {
            foreach ($groups as $key => $group) {
                echo "<option  value='" . $key . "'>" . $group . "</option>";
            }
        }
        echo "   </select>

                        <div id='div_confirmation'>";
        echo Html::submit(__('Confirm', 'transferticketentity'), ['id' => 'tt_btn_open_modal_form', 'class' => 'btn btn-primary',]);
        echo "   </div>
                    </div>";

        echo Html::hidden("technician_profile", ["value" => "$technician_profile"]);
        echo Html::hidden("id_ticket", ["value" => "$id_ticket"]);
        echo Html::hidden("id_user", ["value" => "$id_user"]);
//        echo Html::hidden("theServer", ["value" => "$theServer"]);

        echo "
                </div>

                <dialog id='tt_modal_form_adder' class='tt_modal'>
                    <h2>" . __("Confirm transfer ?", "transferticketentity") . "</h2>
                    <p>" . __(
                        "Once the transfer has been completed, the ticket will remain visible only if you have the required rights.",
                        "transferticketentity"
                    ) . "</p>
                    <div class='justification'>
                        <label for='justification'>" . __("Please explain your transfer", "transferticketentity") . " </label>
                        <textarea id='justification' name='justification' ></textarea>
                    </div>
                    <p class='adv-msg'>" . __(
                            "Warning, category will be reset if it does not exist in the target entity.",
                            "transferticketentity"
                        ) . "</p>

                    <div>";
        echo Html::submit(__('Cancel'), ['name' => 'canceltransfert', 'class' => 'btn btn-danger', 'id' => 'canceltransfert']);
        echo Html::submit(
            __('Confirm', 'transferticketentity'),
            ['name' => 'transfertticket', 'class' => 'btn btn-success','id' => 'transfertticket']

        );
        echo "   </div>
                </dialog>";
        Html::closeForm();
//        self::javascriptTranslate();
    }

    /**
     * Translate text added with JavaScript
     *
     * @return $js
     */
    public function javascriptTranslate()
    {
        $addText = __('optional', 'transferticketentity');
        $root = PLUGIN_TRANSFERTICKETENTITY_WEBDIR . '/ajax/getEntitiesRights.php';

        $jsPluginTTE = "
            $.ajax({
                url: '$root',
                method: 'GET',
                success: function (data) {
                    data = JSON.parse(data);

                    if (document.querySelector('.tt_entity_choice') != null) {
                        let explainText = document.getElementById('justification').previousElementSibling.innerHTML;

                        $('#entity_choice').on('change', function (event) {
                            let entityRights = data.filter(e => e.entities_id == entity_choice.value)
                            let justificationRight = entityRights[0]['justification_transfer']
                            let addText = '';

                            if (justificationRight == 1) {
                                addText = ':'
                                document.getElementById('justification').previousElementSibling.innerHTML = explainText + addText;
                            } else {
                                addText = '($addText)' + ' :'
                                document.getElementById('justification').previousElementSibling.innerHTML = explainText + addText;
                            }
                        })
                    }
                },
                error: function (data) {
                    console.log(data);
                }
            });
        ";

        echo Html::scriptBlock($jsPluginTTE);
    }


    /**
     * Checks that the technician or his group is assigned to the ticket
     *
     * @return bool
     */
    public function checkAssign($params)
    {
        global $DB;

        $id_ticket = $params['id_ticket'];
        $id_user = $params['id_user'];
        $groupTech = [];

        $result = $DB->request([
            'SELECT' => 'groups_id',
            'FROM' => 'glpi_groups_users',
            'WHERE' => ['users_id' => $id_user]
        ]);

        foreach ($result as $data) {
            if (!in_array($data, $groupTech)) {
                array_push($groupTech, $data['groups_id']);
            }
        }

        $checkAssignedTech = [];
        $checkAssignedGroup = [];

        $result = $DB->request([
            'SELECT' => 'users_id',
            'FROM' => 'glpi_tickets_users',
            'WHERE' => ['tickets_id' => $id_ticket]
        ]);

        foreach ($result as $data) {
            if (!in_array($data, $checkAssignedTech)) {
                array_push($checkAssignedTech, $data['users_id']);
            }
        }

        $result = $DB->request([
            'SELECT' => 'groups_id',
            'FROM' => 'glpi_groups_tickets',
            'WHERE' => ['tickets_id' => $id_ticket]
        ]);

        foreach ($result as $data) {
            if (!in_array($data, $checkAssignedGroup)) {
                array_push($checkAssignedGroup, $data['groups_id']);
            }
        }

        $var_check = 0;

        if (in_array($id_user, $checkAssignedTech)) {
            $var_check++;
        }

        foreach ($groupTech as $checkAssign) {
            if (in_array($checkAssign, $checkAssignedGroup)) {
                $var_check++;
            }
        }

        if ($var_check >= 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get only the entities with at least one active group
     *
     * @return array $checkEntityETT
     */
    public function checkEntityETT()
    {
        global $DB;

        $result = $DB->request([
            'SELECT' => [
                'E.id',
                'E.entities_id',
                'E.name',
                'TES.allow_entity_only_transfer',
                'TES.justification_transfer',
                'TES.allow_transfer'
            ],
            'FROM' => 'glpi_entities AS E',
            'LEFT JOIN' => [
                'glpi_plugin_transferticketentity_entities_settings AS TES' => [
                    'FKEY' => [
                        'E' => 'id',
                        'TES' => 'entities_id'
                    ]
                ]
            ],
            'WHERE' => ['TES.allow_transfer' => 1],
            'GROUPBY' => 'E.id',
            'ORDER' => 'E.entities_id ASC'
        ]);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data['id']);
        }

        return $array;
    }

    /**
     * Get only the groups belonging to the selected entity
     *
     * @return array $checkGroup
     */
    public function checkGroup($params)
    {
        global $DB;

        $entity_choice = $params['entity_choice'];

        $result = $DB->request([
            'SELECT' => 'glpi_groups.id',
            'FROM' => 'glpi_groups',
            'LEFT JOIN' => [
                'glpi_entities' => [
                    'FKEY' => [
                        'glpi_groups' => 'entities_id',
                        'glpi_entities' => 'id'
                    ]
                ]
            ],
            'WHERE' => ['glpi_groups.is_assign' => 1, 'glpi_entities.id' => $entity_choice],
            'ORDER' => 'glpi_entities.id ASC'
        ]);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data['id']);
        }

        return $array;
    }

    /**
     * Get the name of the selected entity
     *
     * @return $data
     */
    public function theEntity($params)
    {
        global $DB;
        $entity_choice = $params['entity_choice'];

        $result = $DB->request([
            'SELECT' => 'name',
            'FROM' => 'glpi_entities',
            'WHERE' => ['id' => $entity_choice]
        ]);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data['name']);
        }

        return $array[0];
    }

    /**
     * Get selected entity rights
     *
     * @return array
     */
    public function checkEntityRight($params)
    {
        global $DB;

        $entity_choice = $params['entity_choice'];

        $result = $DB->request([
            'FROM' => 'glpi_plugin_transferticketentity_entities_settings',
            'WHERE' => ['entities_id' => $entity_choice]
        ]);

        $array = [];

        foreach ($result as $data) {
            $array['allow_entity_only_transfer'] = $data['allow_entity_only_transfer'];
            $array['justification_transfer'] = $data['justification_transfer'];
            $array['allow_transfer'] = $data['allow_transfer'];
            $array['keep_category'] = $data['keep_category'];
            $array['itilcategories_id'] = $data['itilcategories_id'];
        }

        return $array;
    }

    /**
     * Get the selected group name
     *
     * @return $data
     */
    public function theGroup($params)
    {
        global $DB;

        if (!empty($params['group_choice'])) {
            $group_choice = $params['group_choice'];

            $result = $DB->request([
                'SELECT' => 'name',
                'FROM' => 'glpi_groups',
                'WHERE' => ['id' => $group_choice]
            ]);

            $array = [];

            foreach ($result as $data) {
                array_push($array, $data['name']);
            }

            return $array[0];
        } else {
            return false;
        }
    }

    /**
     * Check if category exist in target entity
     *
     * @return bool
     */
    public function checkExistingCategory($params)
    {
        global $DB;

        $id_ticket = $params['id_ticket'];
        $targetEntity = $params['entity_choice'];

        $result = $DB->request([
            'SELECT' => 'itilcategories_id',
            'FROM' => 'glpi_tickets',
            'WHERE' => ['id' => $id_ticket]
        ]);

        $getTicketCategory = '';

        foreach ($result as $data) {
            $getTicketCategory = $data['itilcategories_id'];
        }

        $result = $DB->request([
            'FROM' => 'glpi_entities',
            'WHERE' => ['id' => $targetEntity]
        ]);

        $ancestorsEntities = [];

        foreach ($result as $data) {
            if ($data['ancestors_cache']) {
                $ancestorsEntities = $data['ancestors_cache'];
                $ancestorsEntities = json_decode($ancestorsEntities, true);
                array_push($ancestorsEntities, $targetEntity);
            } else {
                array_push($ancestorsEntities, 0);
            }
        }

        $result = $DB->request([
            'FROM' => 'glpi_itilcategories',
            'WHERE' => ['id' => $getTicketCategory]
        ]);

        $getEntitiesFromCategoryTicket = '';
        $isRecursiveCategory = '';

        foreach ($result as $data) {
            $getEntitiesFromCategoryTicket = $data['entities_id'];
            $isRecursiveCategory = $data['is_recursive'];
        }

        if (!$isRecursiveCategory) {
            if ($getEntitiesFromCategoryTicket == $targetEntity) {
                $isRecursiveCategory = true;
            }
        }

        if (in_array($getEntitiesFromCategoryTicket, $ancestorsEntities) && $isRecursiveCategory) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * If the profile is authorised, add an extra tab
     *
     * @param object $item Ticket
     * @param int $withtemplate 0
     *
     * @return "Entity ticket transfer"
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Ticket') {
            return self::createTabEntry(__("Transfer Ticket Entity", "transferticketentity"));
        }
        return '';
    }

    /**
     * If we are on tickets, an additional tab is displayed
     *
     * @param object $item Ticket
     * @param int $tabnum 1
     * @param int $withtemplate 0
     *
     * @return true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Ticket') {
            $ticket = new self();
            $ticket->showFormMcv($item);
        }

        return true;
    }

    /**
     * Give the ticket entity
     *
     * @return $data
     */
    public function getTicketEntity($params)
    {
        global $DB;

        $id_ticket = $params['id'];

        $result = $DB->request([
            'SELECT' => ['glpi_entities.id', 'glpi_entities.name'],
            'FROM' => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_entities' => [
                    'FKEY' => [
                        'glpi_tickets' => 'entities_id',
                        'glpi_entities' => 'id',
                    ],
                ],
            ],
            'WHERE' => ['glpi_tickets.id' => $id_ticket],
        ]);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data['id'], $data['name']);
        }

        return $array;
    }


    /**
     * Get the group assigned to the ticket
     *
     * @return $data
     */
    public function getTicketGroup($params)
    {
        global $DB;

        $id_ticket = $params['id'];

        $result = $DB->request([
            'FROM' => 'glpi_groups_tickets',
            'WHERE' => ['tickets_id' => $id_ticket, 'type' => 2],
        ]);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data['groups_id']);
        }

        return $array;
    }

    public function checkTicketTemplateID($params)
    {
        global $DB;

        $entity_choice = $params['entity_choice'];
        $selectedTemplate = 0;

        while (!$selectedTemplate) {
            $query = [
                'FROM' => 'glpi_entities',
                'WHERE' => ['id' => $entity_choice]
            ];

            foreach ($DB->request($query) as $data) {
                $id = $data['id'];
                $entity_choice = $data['entities_id'];
                $selectedTemplate = $data['tickettemplates_id'];
            }

            if ((!$id && !$selectedTemplate) || ($entity_choice === null && !$selectedTemplate)) {
                return 0;
            }
        }

        return $selectedTemplate;
    }

    /**
     * Check GLPIs mandatory fields
     *
     * @return boolean
     */
    public function checkMandatoryCategory($params)
    {
        global $DB;

        $id_ticket = $params['id_ticket'];
        $tickettemplates_id = self::checkTicketTemplateID($params);

        $ttm_class = 'Ticket' . 'TemplateMandatoryField';
        $ttm = new $ttm_class();
        $mandatoryFields = $ttm->getMandatoryFields($tickettemplates_id);

        $result = $DB->request([
            'FROM' => 'glpi_tickets',
            'WHERE' => ['id' => $id_ticket]
        ]);

        $ticketFields = [];

        foreach ($result as $data) {
            array_push($ticketFields, $data);
        }

        $ticketFields = $ticketFields[0];

        // Check if category field is mandatory
        foreach ($mandatoryFields as $mandatoryField => $MF) {
            if ($mandatoryField == 'itilcategories_id') {
                return true;
            }
        }

        return false;
    }
    /**
     * Carries out the necessary actions for the transfer entity
     *
     * @return void
     */
    public function ticketTransferETT($params)
    {
        global $CFG_GLPI;

        $checkAssign = self::checkAssign($params);
        $checkEntity = self::checkEntityETT();
        $checkGroup = self::checkGroup($params);
        $checkEntityRight = self::checkEntityRight($params);
        $checkExistingCategory = self::checkExistingCategory($params);
        $checkMandatoryCategory = self::checkMandatoryCategory($params);

        $id_ticket = $params['id_ticket'];

        $justification = $params['justification'];
        $requiredGroup = true;

        $theEntity = self::theEntity($params);
        $theGroup = self::theGroup($params);

        $entity_choice = $params['entity_choice'];
        $group_choice = $params['group_choice'];

        if (!isset($params['justification']) || $params['justification'] == '') {
            if ($checkEntityRight['justification_transfer'] == 1) {
                Session::addMessageAfterRedirect(
                    __(
                        "Please explain your transfer",
                        'transferticketentity'
                    ),
                    true,
                    ERROR
                );

                Html::back();

                return false;
            } else {
                $justification = '';
            }
        }

        if (empty($group_choice) && $checkEntityRight['allow_entity_only_transfer'] == 1) {
            Session::addMessageAfterRedirect(
                __(
                    "Please select a valid group",
                    'transferticketentity'
                ),
                true,
                ERROR
            );

            Html::back();

            return false;
        } elseif (empty($group_choice) && $checkEntityRight['allow_entity_only_transfer'] == 0) {
            $requiredGroup = false;
        }

        if (!Session::haveright("plugin_transferticketentity_bypass", READ) && !$checkAssign) {
            Session::addMessageAfterRedirect(
                __(
                    "You must be assigned to the ticket to be able to transfer it",
                    'transferticketentity'
                ),
                true,
                ERROR
            );

            Html::back();
        } elseif (!in_array($entity_choice, $checkEntity)) {
            // Check that the selected entity belongs to those available
            Session::addMessageAfterRedirect(
                __(
                    "Please select a valid entity",
                    'transferticketentity'
                ),
                true,
                ERROR
            );

            Html::back();
        } elseif (!empty($group_choice) && !in_array($group_choice, $checkGroup)) {
            Session::addMessageAfterRedirect(
                __(
                    "Please select a valid group",
                    'transferticketentity'
                ),
                true,
                ERROR
            );

            Html::back();
        } else {
            // Change the entity ticket and set its status to processing (assigned)
            $ticket = new Ticket();

            $ticket_update = [
                'id' => $id_ticket,
                'entities_id' => $entity_choice,
            ];

            if ($theGroup) {
                $ticket_status = ['status' => 2];
                $ticket_update = array_merge($ticket_update, $ticket_status);
            } else {
                $ticket_status = ['status' => 1];
                $ticket_update = array_merge($ticket_update, $ticket_status);
            }

            // In case keep_category is at yes and category doesn't exist, reset category's ticket
            if ($checkEntityRight['keep_category'] && !$checkExistingCategory) {
                $ticket_category = ['itilcategories_id' => 0];
                $ticket_update = array_merge($ticket_update, $ticket_category);
            }

            if (!$checkEntityRight['keep_category']) {
                if ($checkEntityRight['itilcategories_id'] == null) {
                    $ticket_category = ['itilcategories_id' => 0];
                } else {
                    $ticket_category = ['itilcategories_id' => $checkEntityRight['itilcategories_id']];
                }
                $ticket_update = array_merge($ticket_update, $ticket_category);
            }

            // If category is mandatory with GLPIs template and category will be null
            if ($ticket_category['itilcategories_id'] == 0
                && $checkMandatoryCategory) {
                Session::addMessageAfterRedirect(
                    __(
                        "Category will be set to null but its configured as mandatory in GLPIs template, please contact your administrator.",
                        'transferticketentity'
                    ),
                    true,
                    ERROR
                );

                Html::back();

                return false;
            }

            // Remove the link with the current user
            $delete_link_user = [
                'tickets_id' => $id_ticket,
                'type' => CommonITILActor::ASSIGN
            ];

            $ticket_user = new Ticket_User();
            $found_user = $ticket_user->find($delete_link_user);

            foreach ($found_user as $id => $tu) {
                //delete user
                $ticket_user->delete(['id' => $id]);
            }

            // Remove the link with the current group
            $delete_link_group = [
                'tickets_id' => $id_ticket,
                'type' => CommonITILActor::ASSIGN
            ];

            $group_ticket = new Group_Ticket();
            $found_group = $group_ticket->find($delete_link_group);

            foreach ($found_group as $id => $tu) {
                //delete group
                $group_ticket->delete(['id' => $id]);
            }

            $ticket->update($ticket_update);

            if ($requiredGroup) {
                // Change group ticket
                $group_check = [
                    'tickets_id' => $id_ticket,
                    'groups_id' => $group_choice,
                    'type' => CommonITILActor::ASSIGN
                ];

                if (!$group_ticket->find($group_check)) {
                    $group_ticket->add($group_check);
                } else {
                    $group_ticket->update($group_check);
                }
            }

            $groupText = "<br> <br> $justification";

            if ($theGroup) {
                $groupText = __("in the group", "transferticketentity") . " $theGroup \n <br> <br> $justification";
            }

            // Log the transfer in a task
            $task = new TicketTask();
            $task->add([
                'tickets_id' => $id_ticket,
                'is_private' => true,
                'state' => Planning::INFO,
                'content' => __(
                    "Escalation to",
                    "transferticketentity"
                ) . " $theEntity " . $groupText
            ]);

            $ticket = new ticket();
            $ticket->getFromDB($id_ticket);
            Session::addMessageAfterRedirect(
                __(
                    "Successful transfer for ticket n° : ",
                    "transferticketentity"
                ) . $ticket->getLink(),
                true,
                INFO
            );

            Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
        }
    }
}
