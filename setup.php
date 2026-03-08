<?php

/**
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
 @author    Yannick Comba <y.comba@maine-et-loire.fr>
 @copyright 2015-2023 Département de Maine et Loire plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            https://www.gnu.org/licenses/gpl-3.0.html
 @link      https://github.com/departement-maine-et-loire/
 --------------------------------------------------------------------------
*/

use GlpiPlugin\Transferticketentity\Entity;
use GlpiPlugin\Transferticketentity\Profile;
use GlpiPlugin\Transferticketentity\Ticket;


define('TRANSFERTICKETENTITY_VERSION', '1.1.4');

if (!defined("PLUGIN_TRANSFERTICKETENTITY_DIR")) {
    define("PLUGIN_TRANSFERTICKETENTITY_WEBDIR", Plugin::getWebDir("transferticketentity", false));
    define("PLUGIN_TRANSFERTICKETENTITY_FULLWEBDIR", Plugin::getWebDir("transferticketentity"));
}
function plugin_init_transferticketentity()
{
    global $PLUGIN_HOOKS;

    // Add a tab for profiles and tickets
    Plugin::registerClass(Ticket::class, ['addtabon' => 'Ticket']);
    Plugin::registerClass(Entity::class, ['addtabon' => 'Entity']);

    $PLUGIN_HOOKS['change_profile']['transferticketentity'] = [Profile::class, 'initProfile'];

    Plugin::registerClass(Profile::class, ['addtabon' => ['Profile']]);

    $PLUGIN_HOOKS['add_css']['transferticketentity'][] = "css/style.css";

    $PLUGIN_HOOKS['csrf_compliant']['transferticketentity'] = true;
}

function plugin_version_transferticketentity()
{

    return [
        'name'           => 'TransferTicketEntity',
        'version'        => TRANSFERTICKETENTITY_VERSION,
        'author'         => 'Yannick COMBA & <a href="https://blogglpi.infotel.com">Infotel</a>',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/InfotelGLPI/transferticketentity',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
                'max' => '11.0',
                'dev' => false
            ]
        ]];
}


function plugin_transferticketentity_options()
{
    return [
        Plugin::OPTION_AUTOINSTALL_DISABLED => true,
    ];
}
