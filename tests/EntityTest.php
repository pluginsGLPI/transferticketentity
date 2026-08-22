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

namespace GlpiPlugin\Transferticketentity\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Transferticketentity\Entity;

class EntityTest extends DbTestCase
{
    public function testEntitySettingsCanBeCreatedAndRetrieved(): void
    {
        $this->login('glpi', 'glpi');

        $item = $this->createItem(Entity::class, [
            'entities_id'               => 0,
            'allow_entity_only_transfer' => 1,
            'justification_transfer'    => 0,
            'allow_transfer'            => 1,
            'keep_category'             => 0,
            'itilcategories_id'         => 0,
        ]);

        $this->assertGreaterThan(0, $item->getID());
        $this->assertSame(1, $item->getField('allow_transfer'));
    }

    public function testGetInstanceReturnsFields(): void
    {
        $this->login('glpi', 'glpi');

        $this->createItem(Entity::class, [
            'entities_id'               => 0,
            'allow_entity_only_transfer' => 0,
            'justification_transfer'    => 0,
            'allow_transfer'            => 1,
            'keep_category'             => 0,
            'itilcategories_id'         => 0,
        ]);

        $result = Entity::getInstance(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allow_transfer', $result);
    }

    public function testGetInstanceReturnsFalseForUnknownEntity(): void
    {
        $this->login('glpi', 'glpi');

        $result = Entity::getInstance(999999);

        $this->assertFalse($result);
    }

    public function testPrepareInputForAddStripsUnknownKeys(): void
    {
        $this->login('glpi', 'glpi');

        $entity = new Entity();
        $result = $entity->prepareInputForAdd([
            'entities_id'   => 0,
            'allow_transfer' => 1,
            'unknown_key'   => 'should-be-stripped',
        ]);

        $this->assertArrayNotHasKey('unknown_key', $result);
        $this->assertArrayHasKey('allow_transfer', $result);
    }

    public function testCheckEntityRightReturnsExpectedKeys(): void
    {
        $this->login('glpi', 'glpi');

        $this->createItem(Entity::class, [
            'entities_id'               => 0,
            'allow_entity_only_transfer' => 0,
            'justification_transfer'    => 0,
            'allow_transfer'            => 1,
            'keep_category'             => 0,
            'itilcategories_id'         => 0,
        ]);

        $result = Entity::checkEntityRight(['entity_choice' => 0]);

        $this->assertArrayHasKey('allow_transfer', $result);
    }

    public function testAvailableCategoriesReturnsArray(): void
    {
        $this->login('glpi', 'glpi');

        $entity = new Entity();
        $result = $entity->availableCategories(0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
    }
}
