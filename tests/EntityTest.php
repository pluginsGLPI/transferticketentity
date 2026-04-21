<?php

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
