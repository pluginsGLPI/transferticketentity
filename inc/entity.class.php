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

class PluginTransferticketentityEntity extends CommonDBTM
{
    public static $rightname = "entity";

    public static function getTable($classname = null)
    {
        return "glpi_plugin_transferticketentity_entities_settings";
    }

    public static function getInstance($entities_id)
    {
        $temp = new self();
        if ($temp->getFromDBByCrit(['entities_id' => $entities_id])) {
            return $temp->fields;
        }
        return false;
    }

    /**
     * If category belong to ancestor, return it
     *
     * @return array
     */
    public function availableCategories()
    {
        global $DB;
        $entity = $_REQUEST['id'];
        $allItilCategories = [0 => Dropdown::EMPTY_VALUE];

        $result = $DB->request([
            'FROM' => 'glpi_entities',
            'WHERE' => ['id' => $entity],
        ]);

        $ancestorsEntities = [];

        foreach ($result as $data) {
            if ($data['ancestors_cache']) {
                $ancestorsEntities = $data['ancestors_cache'];
                $ancestorsEntities = json_decode($ancestorsEntities, true);
                array_push($ancestorsEntities, $entity);
            } else {
                array_push($ancestorsEntities, 0);
            }
        }

        foreach ($ancestorsEntities as $ancestorEntity) {
            if ($ancestorEntity == $entity) {
                $result = $DB->request([
                    'FROM' => 'glpi_itilcategories',
                    'WHERE' => ['entities_id' => $ancestorEntity],
                ]);

                foreach ($result as $data) {
                    $allItilCategories[$data['id']] = $data['name'];
                }
            } else {
                $result = $DB->request([
                    'FROM' => 'glpi_itilcategories',
                    'WHERE' => ['entities_id' => $ancestorEntity, 'is_recursive' => 1],
                ]);

                foreach ($result as $data) {
                    $allItilCategories[$data['id']] = $data['name'];
                }
            }
        }

        return $allItilCategories;
    }

    /**
     *
     * @param object $item Entity
     * @param int $withtemplate 0
     *
     * @return "Entity ticket transfer"
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Entity') {
            return self::createTabEntry(__("Transfer Ticket Entity", "transferticketentity"));
        }
        return '';
    }

    /**
     *
     * @param object $item Ticket
     * @param int $tabnum 1
     * @param int $withtemplate 0
     *
     * @return true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Entity') {
            $entity = new self();
            $entity->showFormMcv($item);
        }

        return true;
    }


    /**
     * Display the ticket transfer form
     *
     * @return void
     */
    public function showFormMcv($item)
    {

        echo Html::script(PLUGIN_TRANSFERTICKETENTITY_WEBDIR . "/js/entitySettings.js");

        $checkRights = new self();
        $checkRights->getFromDBByCrit(['entities_id' => $item->getID()]);

        $availableCategories = self::availableCategories();

        if (empty($checkRights->fields)) {
            $checkRights->fields['allow_entity_only_transfer'] = 0;
            $checkRights->fields['justification_transfer'] = 0;
            $checkRights->fields['allow_transfer'] = 0;
            $checkRights->fields['keep_category'] = 0;
            $checkRights->fields['itilcategories_id'] = 0;
        }

        echo "<div class='firstbloc'>";

        if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
            echo "<form class='transferticketentity' method='post' action='" . self::getFormURL() . "'>";
        }

        echo "<table class='tab_cadre_fixe'>";
        echo "<tbody>";
        echo "<tr>";
        echo "<th>";
        echo __("Settings Transfer Ticket Entity", "transferticketentity");
        echo "</th>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Allow Transfer function', 'transferticketentity');
        echo "&nbsp;";
        echo "&nbsp;";
        echo Dropdown::showYesNo(
            'allow_transfer',
            $checkRights->fields['allow_transfer'],
            -1,
            ['display' => false, 'class' => 'allow_transfer']
        );
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1' id='allow_entity_only_transfer'>";
        echo "<td>";
        echo __('Assigned group required', 'transferticketentity');
        echo "&nbsp;";
        echo "&nbsp;";
        echo Dropdown::showYesNo(
            'allow_entity_only_transfer',
            $checkRights->fields['allow_entity_only_transfer'],
            -1,
            ['display' => false]
        );
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1' id='justification_transfer'>";
        echo "<td>";
        echo __('Justification required', 'transferticketentity');
        echo "&nbsp;";
        echo "&nbsp;";
        echo Dropdown::showYesNo(
            'justification_transfer',
            $checkRights->fields['justification_transfer'],
            -1,
            ['display' => false]
        );
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1' id='keep_category'>";
        echo "<td>";
        echo __('Keep category after transfer', 'transferticketentity');
        echo "&nbsp;";
        echo "&nbsp;";
        echo Dropdown::showYesNo(
            'keep_category',
            $checkRights->fields['keep_category'],
            -1,
            ['display' => false]
        );
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1' id='itilcategories_id'>";
        echo "<td>";
        echo __('Default category', 'transferticketentity');
        echo "&nbsp;";
        echo "&nbsp;";
        Dropdown::showFromArray(
            'itilcategories_id',
            $availableCategories,
            ['value' => $checkRights->fields['itilcategories_id'],
                'class' => 'itilcategories_id']
        );
        echo "</td>";
        echo "</tr>";
        echo "</tbody>";
        echo "</table>";
        echo Html::hidden("entities_id", ["value" => $item->getID()]);
        if ($checkRights->getID()) {
            echo Html::hidden("id", ["value" => $checkRights->getID()]);
        }

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>\n";
            Html::closeForm();
        }

        echo "</div>";

    }

    public static function getEntitiesRights()
    {
        global $DB;

        $result = $DB->request([
            'SELECT' => [
                'entities_id',
                'allow_entity_only_transfer',
                'justification_transfer',
                'allow_transfer',
                'keep_category',
            ],
            'FROM' => 'glpi_plugin_transferticketentity_entities_settings',
            'ORDER' => ['entities_id ASC'],
        ]);

        $array = [];

        foreach ($result as $data) {
            array_push($array, $data);
        }

        return $array;
    }
}
