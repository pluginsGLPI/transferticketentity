<?php

/*
 -------------------------------------------------------------------------
 LICENSE

 This file is part of Transferticketentity plugin for GLPI.

 Transferticketentity is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Transferticketentity is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @category  Ticket
 @package   Transferticketentity
 @author    Yannick Comba, Xavier Caillaud, Infotel
 @copyright 2015-2026 Transferticketentity team
 @license   AGPL License 3.0 or (at your option) any later version
            https://www.gnu.org/licenses/gpl-3.0.html
 @link      https://github.com/pluginsGLPI/transferticketentity/
 --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Transferticketentity\Entity;
use GlpiPlugin\Transferticketentity\Ticket;

Session::checkRight("entity", UPDATE);

$config = new Entity();

if (isset($_POST["update"])) {
    $entities_id = (int) ($_POST['entities_id'] ?? 0);

    // The "entity" UPDATE right is not entity-aware on its own: verify the administrator
    // actually manages the target entity before creating, updating or deleting its transfer
    // settings, otherwise an entity admin could tamper with the policy of an entity outside
    // their scope by posting an arbitrary entities_id/id.
    if (!Session::haveAccessToEntity($entities_id)) {
        throw new AccessDeniedHttpException();
    }

    $config_data = $config::getInstance($entities_id);
    if (empty($config_data)) {
        unset($_POST['id']);
        $config->add($_POST);
    } else {
        if ((int) ($_POST['allow_transfer'] ?? 0) == 0) {
            // Delete by the id resolved from the verified entities_id, never the raw POST id.
            $config->delete(['id' => (int) $config_data['id']]);
        } else {
            $params['entity_choice'] = $entities_id;
            $checkMandatoryCategory = Ticket::checkMandatoryCategory($params);

            if ($checkMandatoryCategory
                && (int) ($_POST['keep_category'] ?? 0) == 0
                && (int) ($_POST['itilcategories_id'] ?? 0) == 0) {
                Session::addMessageAfterRedirect(
                    __(
                        "The category is mandatory in the ticket template assigned to the entity",
                        'transferticketentity'
                    ),
                    true,
                    ERROR
                );
            } else {
                // Never trust the raw POST id: pin both id and entities_id to the row
                // resolved from the verified entity, otherwise a user allowed on entity X
                // could overwrite the settings row of an unrelated entity Z by posting its id.
                $_POST['id'] = (int) $config_data['id'];
                $_POST['entities_id'] = $entities_id;
                $config->update($_POST);
            }
        }
    }
    Html::back();
}
