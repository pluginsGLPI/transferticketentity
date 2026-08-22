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

use GlpiPlugin\Transferticketentity\Entity;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkRight('plugin_transferticketentity_use', READ);
// This endpoint only feeds the transfer form, whose display and submission both
// require `ticket` UPDATE (see Ticket::showFormMcv / launchTicketTransfer). Gate
// the AJAX surface on the same capability so a profile holding only the plugin
// READ right cannot enumerate target-entity groups and mandatory-policy flags.
Session::checkRight('ticket', UPDATE);

$getEntitiesRights = Entity::getEntitiesRights();

echo json_encode($getEntitiesRights);
