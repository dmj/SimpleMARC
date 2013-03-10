<?php

/**
 * This file is part of SimpleMARC.
 *
 * SimpleMARC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SimpleMARC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SimpleMARC.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2013 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */

namespace HAB\SimpleMARC;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * Unit tests for SimpleMARC record.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2013 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */
class RecordTest extends TestCase
{
    private $record;

    protected function setup ()
    {
        $data = file_get_contents(PHPUNIT_FIXTURES . '/record.mrc');
        $this->record = new Record($data);
    }

    public function testLeader ()
    {
        $record = $this->record;
        $leader = $record->leader();
        $this->assertEquals('01089nam a2200241 cb4500', $leader);
    }

    public function testSelect ()
    {
        $record = $this->record;
        $fields = $record->select('^245/10$');
        $this->assertCount(1, $fields);
        $field = reset($fields);
        $this->assertCount(1, $field);
        $field = reset($field);
        $this->assertEquals(
            array(
                'a' => array(0 => 'Grundlinien der Philosophie des Rechts oder Naturrecht und Staatswissenschaft im Grundrisse :'),
                'b' => array(1 => 'mit Hegels eigenhändigen Notizen und den mündlichen Zusätzen'),
            ),
            $field
        );
    }
}