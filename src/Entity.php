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
 * @author    Yannick Comba, Xavier Caillaud, Infotel
 * @category  Ticket
 * @copyright 2015-2026 Transferticketentity team
 * @license   AGPL License 3.0 or (at your option) any later version
 * @link      https://github.com/pluginsGLPI/transferticketentity/
 * @package   Transferticketentity
 *            https://www.gnu.org/licenses/gpl-3.0.html
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Transferticketentity;

use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;

class Entity extends CommonDBTM
{
    public static $rightname = "entity";

    public static function getTable($classname = null)
    {
        return "glpi_plugin_transferticketentity_entities_settings";
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return "ti ti-transfer";
    }

    public static function getInstance($entities_id)
    {
        // find() rather than getFromDBByCrit(): a leftover duplicate row must not
        // make the configuration unreachable (getFromDBByCrit() throws on it).
        $rows = (new self())->find(['entities_id' => $entities_id], 'id ASC', 1);
        if ($rows !== []) {
            return reset($rows);
        }
        return false;
    }

    /**
     * If category belong to ancestor, return it
     *
     * @return array|false
     */
    public function prepareInputForAdd($input)
    {
        $input = array_intersect_key($input, array_flip([
            'entities_id',
            'allow_entity_only_transfer',
            'justification_transfer',
            'allow_transfer',
            'keep_category',
            'itilcategories_id',
            'log_type',
        ]));

        // One settings row per entity (also enforced by a unique key)
        if (countElementsInTable(self::getTable(), ['entities_id' => (int) ($input['entities_id'] ?? 0)]) > 0) {
            return false;
        }

        return $this->sanitizeCategoryScope($input, (int) ($input['entities_id'] ?? 0));
    }

    public function prepareInputForUpdate($input)
    {
        $input = array_intersect_key($input, array_flip([
            'id',
            'entities_id',
            'allow_entity_only_transfer',
            'justification_transfer',
            'allow_transfer',
            'keep_category',
            'itilcategories_id',
            'log_type',
        ]));

        // The entity of the edited row is pinned by the controller; never trust a
        // posted entities_id here for the category scope check.
        $entities_id = (int) ($this->fields['entities_id'] ?? $input['entities_id'] ?? 0);

        return $this->sanitizeCategoryScope($input, $entities_id);
    }

    /**
     * Ensure a submitted default category actually belongs to the target entity
     * scope. Without this, an entity administrator could store the id of a category
     * owned by another (out-of-scope) entity, which would later be applied to any
     * ticket transferred into this entity.
     *
     * @param array $input
     * @param int   $entities_id
     * @return array
     */
    private function sanitizeCategoryScope(array $input, int $entities_id): array
    {
        if (!array_key_exists('itilcategories_id', $input)) {
            return $input;
        }

        $available = $this->availableCategories($entities_id);
        $input['itilcategories_id'] = array_key_exists((int) $input['itilcategories_id'], $available)
            ? (int) $input['itilcategories_id']
            : 0;

        return $input;
    }

    public function availableCategories(int $entity_id)
    {
        global $DB;
        $entity = $entity_id;
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
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Gate the transfer-policy tab on the dedicated plugin right, not just native
        // "entity" READ: the transfer configuration should only be visible to profiles
        // actually provisioned for this plugin.
        if (!Session::haveRight('plugin_transferticketentity_use', READ)) {
            return '';
        }
        if ($item->getType() == \Entity::class) {
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
        // Same plugin-right gate as getTabNameForItem: never render the transfer policy
        // to a profile that only holds native "entity" READ without the plugin right.
        if (!Session::haveRight('plugin_transferticketentity_use', READ)) {
            return true;
        }
        if ($item->getType() == \Entity::class) {
            $entity = new self();
            $entity->showFormMcv($item);
        }

        return true;
    }

    /**
     * Display the ticket transfer form
     *
     * @return true
     */
    public function showFormMcv($item)
    {
        $checkRights = new self();
        $config_data = self::getInstance($item->getID());
        if ($config_data !== false) {
            $checkRights->getFromDB($config_data['id']);
        }

        $availableCategories = self::availableCategories($item->getID());

        $params['entity_choice'] = $item->getID();
        $checkMandatoryCategory = Ticket::checkMandatoryCategory($params);

        if (empty($checkRights->fields)) {
            $checkRights->fields['allow_entity_only_transfer'] = 0;
            $checkRights->fields['justification_transfer'] = 0;
            $checkRights->fields['allow_transfer'] = 0;
            $checkRights->fields['keep_category'] = 0;
            $checkRights->fields['itilcategories_id'] = 0;
            $checkRights->fields['log_type'] = 0;
        }

        $target = self::getFormURL();

        TemplateRenderer::getInstance()->display(
            '@transferticketentity/config.html.twig',
            [
                // Same right as front/entity.form.php, which receives the form
                'can_edit' => Session::haveRight(self::$rightname, UPDATE),
                'item' => $checkRights,
                'action' => $target,
                'id' => $checkRights->getID(),
                'entities_id' => $item->getID(),
                'availableCategories' => $availableCategories,
                'checkMandatoryCategory' => $checkMandatoryCategory,
                'log_type_options' => [
                    0 => _n('Followup', 'Followups', 1),
                    1 => _n('Task', 'Tasks', 1),
                ],
            ],
        );

        return true;
    }


    /**
     * Get selected entity rights
     *
     * @return array
     */
    public static function checkEntityRight($params)
    {
        $array = [];
        $entity_config = new self();
        $entities = $entity_config->find(['entities_id' => $params['entity_choice']]);

        foreach ($entities as $data) {
            $array['allow_entity_only_transfer'] = $data['allow_entity_only_transfer'];
            $array['justification_transfer'] = $data['justification_transfer'];
            $array['allow_transfer'] = $data['allow_transfer'];
            $array['keep_category'] = $data['keep_category'];
            $array['itilcategories_id'] = $data['itilcategories_id'];
            $array['log_type'] = $data['log_type'] ?? 0;
        }

        return $array;
    }
}
