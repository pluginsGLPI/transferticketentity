<?php

namespace GlpiPlugin\Transferticketentity\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Transferticketentity\Profile;

class ProfileTest extends DbTestCase
{
    public function testGetTypeNameIsNotEmpty(): void
    {
        $this->assertNotEmpty(Profile::getTypeName(1));
    }

    public function testGetAllRightsReturnsTwoEntries(): void
    {
        $this->assertCount(2, Profile::getAllRights());
    }

    public function testGetAllRightsContainsUseField(): void
    {
        $fields = array_column(Profile::getAllRights(), 'field');
        $this->assertContains('plugin_transferticketentity_use', $fields);
    }

    public function testGetAllRightsContainsBypassField(): void
    {
        $fields = array_column(Profile::getAllRights(), 'field');
        $this->assertContains('plugin_transferticketentity_bypass', $fields);
    }

    public function testGetTabNameForItemReturnsEmptyForNonProfile(): void
    {
        $this->login('glpi', 'glpi');

        $profile = new Profile();
        $ticket  = new \Ticket();

        $this->assertSame('', $profile->getTabNameForItem($ticket));
    }

    public function testGetTabNameForItemReturnsLabelForCentralProfile(): void
    {
        $this->login('glpi', 'glpi');

        $profile     = new Profile();
        $glpiProfile = new \Profile();
        $glpiProfile->fields['interface'] = 'central';

        $this->assertNotEmpty($profile->getTabNameForItem($glpiProfile));
    }

    public function testTranslateARightReturnsZeroForEmpty(): void
    {
        $this->assertSame(0, Profile::translateARight(''));
    }

    public function testTranslateARightReturnsReadConstantForR(): void
    {
        $this->assertSame(READ, Profile::translateARight('r'));
    }

    public function testTranslateARightReturnsZeroForUnknown(): void
    {
        $this->assertSame(0, Profile::translateARight('x'));
    }
}
