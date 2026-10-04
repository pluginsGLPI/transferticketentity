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

// Entity configuration (templates/config.html.twig): the transfer parameters are shown while
// the transfer is allowed, the default category while the category is not kept. The slider
// macro of the core has no on_change option, hence the listener on the checkboxes. The tab is
// loaded over AJAX after this script, so the listener is delegated to the document.
(function () {
    const toggles = {
        allow_transfer: {block: 'transfer_params', show_when: true},
        keep_category: {block: 'category_block', show_when: false},
    };

    document.addEventListener('change', function (event) {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox' || !(input.name in toggles)) {
            return;
        }
        const toggle = toggles[input.name];
        const block = document.getElementById(toggle.block);
        if (block !== null) {
            block.classList.toggle('d-none', input.checked !== toggle.show_when);
        }
    });
})();
