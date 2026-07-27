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

use GlpiPlugin\Transferticketentity\Profile;

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_transferticketentity_install()
{
    global $DB;

    Profile::createFirstAccess($_SESSION["glpiactiveprofile"]["id"]);

    $default_charset = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

    if (!$DB->TableExists("glpi_plugin_transferticketentity_entities_settings")) {
        $query = "CREATE TABLE `glpi_plugin_transferticketentity_entities_settings` (
            `id` int {$default_key_sign} NOT NULL auto_increment,
            `entities_id` int {$default_key_sign} NOT NULL,
            `allow_entity_only_transfer` BOOLEAN NOT NULL DEFAULT 0,
            `justification_transfer` BOOLEAN NOT NULL DEFAULT 0,
            `allow_transfer` BOOLEAN NOT NULL DEFAULT 0,
            `keep_category` BOOLEAN NOT NULL DEFAULT 0,
            `itilcategories_id` INT {$default_key_sign},
            `log_type` TINYINT NOT NULL DEFAULT 0,
            PRIMARY KEY  (`id`),
            KEY `entities_id` (`entities_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

        $DB->doQuery($query);
    }

    // GLPI does not use real DB-level foreign keys. A FK referencing glpi_entities.id
    // blocks core migrations that run `ALTER TABLE glpi_entities MODIFY id ...`
    // (MySQL error 1833). Drop any leftover FK constraint created by older versions.
    $fk_result = $DB->doQuery(
        "SELECT `CONSTRAINT_NAME` FROM `information_schema`.`KEY_COLUMN_USAGE`
         WHERE `TABLE_SCHEMA` = DATABASE()
           AND `TABLE_NAME` = 'glpi_plugin_transferticketentity_entities_settings'
           AND `REFERENCED_TABLE_NAME` IS NOT NULL"
    );
    if ($fk_result) {
        while ($fk_row = $DB->fetchAssoc($fk_result)) {
            $DB->doQuery(
                "ALTER TABLE `glpi_plugin_transferticketentity_entities_settings`
                 DROP FOREIGN KEY `{$fk_row['CONSTRAINT_NAME']}`"
            );
        }
    }

    if (!$DB->fieldExists('glpi_plugin_transferticketentity_entities_settings', 'log_type')) {
        $DB->doQuery("ALTER TABLE `glpi_plugin_transferticketentity_entities_settings`
            ADD COLUMN `log_type` TINYINT NOT NULL DEFAULT 0");
    }

    return true;
}

/**
 * Uninstall hook
 *
 * @return boolean
 */
function plugin_transferticketentity_uninstall()
{
    global $DB;

    // Plugin tables deletion
    $tables = ["glpi_plugin_transferticketentity_entities_settings"];

    foreach ($tables as $table) {
        $DB->dropTable($table);
    }

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }
    return true;
}
