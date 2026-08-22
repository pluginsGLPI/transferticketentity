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
use GlpiPlugin\Transferticketentity\Ticket;

Session::checkRight('plugin_transferticketentity_use', READ);
// This endpoint only feeds the transfer form, whose display and submission both
// require `ticket` UPDATE (see Ticket::showFormMcv / launchTicketTransfer). Gate
// the AJAX surface on the same capability so a profile holding only the plugin
// READ right cannot enumerate target-entity groups and mandatory-policy flags.
Session::checkRight('ticket', UPDATE);

if (strpos($_SERVER['PHP_SELF'], "showentitygroups.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
} elseif (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

if (isset($_POST['entity_selection'])) {
    $entitites_id = (int) $_POST['entity_selection'];

    // Only authorized transfer targets (allow_transfer = 1) may be queried here.
    // Without this check any technician holding the plugin READ right could enumerate
    // the assignable group names of arbitrary entities outside their scope. Transfer
    // targets are intentionally NOT restricted by haveAccessToEntity(): the feature
    // must allow transferring a ticket into an entity the technician does not manage.
    if (!in_array($entitites_id, array_map('intval', Ticket::checkEntityETT()), true)) {
        throw new AccessDeniedHttpException();
    }

    $getGroupEntities = Ticket::getGroupEntities($entitites_id);

    $groups[0] = Dropdown::EMPTY_VALUE;
    foreach ($getGroupEntities as $key => $group) {
        $groups[$key] = $group;
    }
    if (count($groups) > 0) {
        Dropdown::showFromArray(
            'group_choice',
            $groups,
        );
    } else {
        echo "<div class='alert alert-danger'>";
        echo __(
            "No group found with « Assigned to » right while a group is required. Transfer impossible.",
            "transferticketentity",
        );
        echo "</div>";
    }
}
