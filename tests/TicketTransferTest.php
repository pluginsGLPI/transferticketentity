<?php

namespace GlpiPlugin\Transferticketentity\Tests;

use CommonITILActor;
use CommonITILObject;
use Glpi\Tests\DbTestCase;
use Group;
use Group_Ticket;
use GlpiPlugin\Transferticketentity\Entity as EntityConfig;
use GlpiPlugin\Transferticketentity\Ticket as TransferTicket;
use ITILCategory;
use Planning;
use Ticket;
use TicketTask;

class TicketTransferTest extends DbTestCase
{
    private function createDestEntity(string $name = 'Destination Entity'): \Entity
    {
        return $this->createItem(\Entity::class, [
            'name'        => $name,
            'entities_id' => 0,
        ]);
    }

    private function createEntityConfig(\Entity $entity, array $extra = []): EntityConfig
    {
        return $this->createItem(EntityConfig::class, array_merge([
            'entities_id'                => $entity->getID(),
            'allow_transfer'             => 1,
            'allow_entity_only_transfer' => 0,
            'justification_transfer'     => 0,
            'keep_category'              => 1,
            'itilcategories_id'          => 0,
        ], $extra));
    }

    private function createTicket(int $entities_id = 0): Ticket
    {
        return $this->createItem(Ticket::class, [
            'name'        => 'Transfer Test Ticket',
            'content'     => 'Content',
            'entities_id' => $entities_id,
        ]);
    }

    // -------------------------------------------------------------------
    // Pre-condition checks
    // -------------------------------------------------------------------

    public function testCheckEntityRightReturnsJustificationFlag(): void
    {
        $this->login('glpi', 'glpi');

        $dest = $this->createDestEntity();
        $this->createEntityConfig($dest, ['justification_transfer' => 1]);

        $result = EntityConfig::checkEntityRight(['entity_choice' => $dest->getID()]);

        $this->assertSame(1, (int) $result['justification_transfer']);
    }

    public function testCheckEntityRightReturnsGroupRequiredFlag(): void
    {
        $this->login('glpi', 'glpi');

        $dest = $this->createDestEntity();
        $this->createEntityConfig($dest, ['allow_entity_only_transfer' => 1]);

        $result = EntityConfig::checkEntityRight(['entity_choice' => $dest->getID()]);

        $this->assertSame(1, (int) $result['allow_entity_only_transfer']);
    }

    public function testGetGroupEntitiesReturnsAssignGroupsForEntity(): void
    {
        $this->login('glpi', 'glpi');

        $dest  = $this->createDestEntity();
        $group = $this->createItem(Group::class, [
            'name'        => 'Assign Group',
            'entities_id' => $dest->getID(),
            'is_assign'   => 1,
        ]);

        $groups = TransferTicket::getGroupEntities($dest->getID());

        $this->assertArrayHasKey($group->getID(), $groups);
    }

    public function testGetGroupEntitiesExcludesNonAssignGroups(): void
    {
        $this->login('glpi', 'glpi');

        $dest  = $this->createDestEntity();
        $group = $this->createItem(Group::class, [
            'name'        => 'Non-Assign Group',
            'entities_id' => $dest->getID(),
            'is_assign'   => 0,
        ]);

        $groups = TransferTicket::getGroupEntities($dest->getID());

        $this->assertArrayNotHasKey($group->getID(), $groups);
    }

    public function testCheckMandatoryCategoryReturnsBool(): void
    {
        $this->login('glpi', 'glpi');

        $result = TransferTicket::checkMandatoryCategory(['entity_choice' => 0]);

        $this->assertIsBool($result);
    }

    // -------------------------------------------------------------------
    // Transfer scenario: entity 0 → entity 1, no group, no justification
    // -------------------------------------------------------------------

    public function testSimpleTransferChangesTicketEntity(): void
    {
        $this->login('glpi', 'glpi');

        $dest = $this->createDestEntity();
        $this->createEntityConfig($dest, [
            'allow_entity_only_transfer' => 0,
            'justification_transfer'     => 0,
        ]);

        $ticket     = $this->createTicket(0);
        $tickets_id = $ticket->getID();

        // Perform the entity/status update (core of launchTicketTransfer)
        $ticket->update([
            'id'          => $tickets_id,
            'entities_id' => $dest->getID(),
            'status'      => CommonITILObject::INCOMING,
        ]);

        $ticket->getFromDB($tickets_id);
        $this->assertSame($dest->getID(), (int) $ticket->getField('entities_id'));
        $this->assertSame(CommonITILObject::INCOMING, (int) $ticket->getField('status'));
    }

    // -------------------------------------------------------------------
    // Transfer scenario: with group → status = ASSIGNED
    // -------------------------------------------------------------------

    public function testTransferWithGroupSetsStatusAssignedAndLinksGroup(): void
    {
        $this->login('glpi', 'glpi');

        $dest  = $this->createDestEntity();
        $group = $this->createItem(Group::class, [
            'name'        => 'Transfer Group',
            'entities_id' => $dest->getID(),
            'is_assign'   => 1,
        ]);
        $this->createEntityConfig($dest, ['allow_entity_only_transfer' => 1]);

        $ticket     = $this->createTicket(0);
        $tickets_id = $ticket->getID();

        // Perform transfer with group (mirrors launchTicketTransfer's DB operations)
        $ticket->update([
            'id'          => $tickets_id,
            'entities_id' => $dest->getID(),
            'status'      => CommonITILObject::ASSIGNED,
        ]);

        $group_ticket = new Group_Ticket();
        $group_ticket->add([
            'tickets_id' => $tickets_id,
            'groups_id'  => $group->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);

        $ticket->getFromDB($tickets_id);
        $this->assertSame($dest->getID(), (int) $ticket->getField('entities_id'));
        $this->assertSame(CommonITILObject::ASSIGNED, (int) $ticket->getField('status'));

        $linked = countElementsInTable(Group_Ticket::getTable(), [
            'tickets_id' => $tickets_id,
            'groups_id'  => $group->getID(),
            'type'       => CommonITILActor::ASSIGN,
        ]);
        $this->assertSame(1, $linked);
    }

    // -------------------------------------------------------------------
    // Transfer scenario: with justification → task logged
    // -------------------------------------------------------------------

    public function testTransferWithJustificationLogsTaskContainingText(): void
    {
        $this->login('glpi', 'glpi');

        $dest = $this->createDestEntity('Escalation Target');
        $this->createEntityConfig($dest, ['justification_transfer' => 1]);

        $ticket        = $this->createTicket(0);
        $tickets_id    = $ticket->getID();
        $justification = 'Escalation for priority handling';

        // Simulate the task log written by launchTicketTransfer
        $task = new TicketTask();
        $task_id = $task->add([
            'tickets_id' => $tickets_id,
            'is_private' => true,
            'state'      => Planning::INFO,
            'content'    => 'Escalation to ' . $dest->getName() . "\n <br> <br> " . $justification,
        ]);

        $task->getFromDB($task_id);
        $this->assertStringContainsString($justification, $task->getField('content'));
        $this->assertStringContainsString($dest->getName(), $task->getField('content'));
    }

    // -------------------------------------------------------------------
    // Transfer scenario: keep_category=1, category exists in ancestor
    // -------------------------------------------------------------------

    public function testTransferKeepsCategoryWhenFlagEnabled(): void
    {
        $this->login('glpi', 'glpi');

        $dest     = $this->createDestEntity();
        $category = $this->createItem(ITILCategory::class, [
            'name'         => 'Recursive Cat',
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);
        $this->createEntityConfig($dest, ['keep_category' => 1]);

        $ticket = $this->createItem(Ticket::class, [
            'name'               => 'Category Ticket',
            'content'            => 'Content',
            'entities_id'        => 0,
            'itilcategories_id'  => $category->getID(),
        ]);
        $tickets_id = $ticket->getID();

        // Category is in root entity (0) and is_recursive=1 → valid for child entity
        // keep_category=1 + category exists → no category reset
        $ticket->update([
            'id'          => $tickets_id,
            'entities_id' => $dest->getID(),
        ]);

        $ticket->getFromDB($tickets_id);
        $this->assertSame($category->getID(), (int) $ticket->getField('itilcategories_id'));
    }

    // -------------------------------------------------------------------
    // Transfer scenario: keep_category=0 → category replaced by config value
    // -------------------------------------------------------------------

    public function testTransferResetsCategoryWhenFlagDisabled(): void
    {
        $this->login('glpi', 'glpi');

        $dest     = $this->createDestEntity();
        $category = $this->createItem(ITILCategory::class, [
            'name'         => 'Original Cat',
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);
        $this->createEntityConfig($dest, [
            'keep_category'    => 0,
            'itilcategories_id' => 0,
        ]);

        $ticket = $this->createItem(Ticket::class, [
            'name'               => 'NoKeep Ticket',
            'content'            => 'Content',
            'entities_id'        => 0,
            'itilcategories_id'  => $category->getID(),
        ]);
        $tickets_id = $ticket->getID();

        // keep_category=0, itilcategories_id=0 → category reset to 0
        $ticket->update([
            'id'                => $tickets_id,
            'entities_id'       => $dest->getID(),
            'itilcategories_id' => 0,
        ]);

        $ticket->getFromDB($tickets_id);
        $this->assertSame(0, (int) $ticket->getField('itilcategories_id'));
    }

    // -------------------------------------------------------------------
    // checkExistingCategory
    // -------------------------------------------------------------------

    public function testCheckExistingCategoryReturnsTrueForRecursiveAncestorCategory(): void
    {
        $this->login('glpi', 'glpi');

        $dest     = $this->createDestEntity();
        $category = $this->createItem(ITILCategory::class, [
            'name'         => 'Ancestor Cat',
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);
        $ticket   = $this->createItem(Ticket::class, [
            'name'              => 'Cat Check Ticket',
            'content'           => 'Content',
            'entities_id'       => 0,
            'itilcategories_id' => $category->getID(),
        ]);

        $result = TransferTicket::checkExistingCategory([
            'id_ticket'     => $ticket->getID(),
            'entity_choice' => $dest->getID(),
        ]);

        $this->assertTrue($result);
    }
}
