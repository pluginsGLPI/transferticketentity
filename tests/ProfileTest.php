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

namespace GlpiPlugin\Transferticketentity\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Transferticketentity\Profile;

class ProfileTest extends DbTestCase
{
    public function testGetTypeNameIsNotEmpty(): void
    {
        $this->assertNotEmpty(Profile::getTypeName(1));
    }

    public function testGetAllRightsReturnsThreeEntries(): void
    {
        $this->assertCount(3, Profile::getAllRights());
    }

    public function testGetAllRightsContainsMassiveField(): void
    {
        $fields = array_column(Profile::getAllRights(), 'field');
        $this->assertContains('plugin_transferticketentity_massive', $fields);
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
