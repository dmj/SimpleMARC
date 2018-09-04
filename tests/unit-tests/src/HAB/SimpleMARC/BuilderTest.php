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
 * along with SimpleMARC.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */

namespace HAB\SimpleMARC;

use PHPUnit_Framework_TestCase as TestCase;

/**
 * Unit tests for the Builder class.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */
class BuilderTest extends TestCase
{
    public function testBuildEmptyRecord ()
    {
        $builder = new Builder();
        $this->assertEquals('00025nam a2200025uu 4500', $builder->build());
    }

    public function testBuildRecord ()
    {
        $builder = new Builder();
        $builder->addControlField('001', '12345');
        $builder->addControlField('003', 'DE-23');
        $builder->addDataField('100', '3 ', array(array('a', 'Mustermann, Max')));

        $data = $builder->build();
        $record = new Record($data);
        $fields = $record->select('001|003');
        $this->assertCount(2, $fields);
        $this->assertEquals('12345', $fields['001'][0]);
        $this->assertEquals('DE-23', $fields['003'][0]);
        $fields = $record->select('100');
        $this->assertCount(1, $fields);
    }
}
