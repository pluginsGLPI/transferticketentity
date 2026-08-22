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

function see_params(value) {
    const block = document.getElementById("transfer_params");
    if (value == 1) {
        block.classList.remove("d-none");
    } else {
        block.classList.add("d-none");
    }
}

function see_category(value) {
    const block = document.getElementById("category_block");
    if (value == 0) {
        block.classList.remove("d-none");
    } else {
        block.classList.add("d-none");
    }
}
