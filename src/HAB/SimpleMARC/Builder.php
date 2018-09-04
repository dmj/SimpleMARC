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

use InvalidArgumentException;

/**
 * MARC record builder.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */
class Builder
{
    /**
     * Default leader.
     *
     * @var string
     */
    private static $defaultLeader = '-----nam a22-----uu 4500';

    /**
     * Leader.
     *
     * @var string
     */
    private $leader;

    /**
     * Control fields, indexed by number.
     *
     * @var array
     */
    private $controlFields;

    /**
     * Data fields, indexed by shorthand.
     *
     * @var array
     */
    private $dataFields;

    public function __construct ()
    {
        $this->reset();
    }

    /**
     * Reset the builder.
     *
     * @return self
     */
    public function reset ()
    {
        $this->leader = static::$defaultLeader;
        $this->controlFields = array();
        $this->dataFields = array();
    }

    /**
     * Add a control field.
     *
     * @throws InvalidArgumentException The control field number must match 00[1-9]
     *
     * @param  string $number
     * @param  string $value
     * @return self
     */
    public function addControlField ($number, $value)
    {
        if (!preg_match('@^00[1-9]$@u', $number)) {
            throw new InvalidArgumentException("The control field number must match 00[1-9]: {$number}");
        }
        $this->controlFields[$number] []= $value;
    }

    /**
     * Add a data field.
     *
     * @throws InvalidArgumentException
     *
     * @param  string $number
     * @param  string $indicators
     * @param  array  $subfields
     * @return self
     */
    public function addDataField ($number, $indicators, array $subfields)
    {
        if (!preg_match('@^[1-9][0-9]{2}$@u', $number)) {
            throw new InvalidArgumentException("The data field number must match [1-9][0-9]{2}: {$number}");
        }
        if (!preg_match('@^[0-9 ]{2}$@u', $indicators)) {
            throw new InvalidArgumentException("The indicators' must match [0-9 ]{2}: {$indicators}");
        }
        foreach ($subfields as $subfield) {
            if (!is_array($subfield)) {
                throw new InvalidArgumentException(sprintf("A subfield must be encoded as array: %s", gettype($subfield)));
            }
            if (count($subfield) != 2) {
                throw new InvalidArgumentException(sprintf("A subfield must be encoded as [CODE, VALUE]: %s", print_r($subfield, true)));
            }
            if (strlen($subfield[0]) != 1) {
                throw new InvalidArgumentException("A subfile code must be a single character: {$subfield[0]}");
            }
        }
        $shorthand = sprintf("%s/%s", $number, $indicators);
        $this->dataFields[$shorthand] []= $subfields;
    }

    /**
     * Build the MARC record.
     *
     * @return string
     */
    public function build ()
    {
        $directoryLength = 0;
        $directory = array();
        $data = array();

        ksort($this->controlFields);
        foreach ($this->controlFields as $number => $values) {
            foreach ($values as $value) {
                $data []= $value;
                $directory []= sprintf('%03d%04d%05d', $number, 1 + strlen($value), $directoryLength);
                $directoryLength += strlen($value) + 1;
            }
        }

        ksort($this->dataFields);
        foreach ($this->dataFields as $shorthand => $values) {
            list($number, $ind) = explode('/', $shorthand);
            foreach ($values as $subfields) {
                $value = $ind . Record::SUBFIELD_DELIMITER . $ind . implode(Record::SUBFIELD_DELIMITER, array_map('implode', $subfields));
                $data []= $value;
                $directory []= sprintf('%03d%04d%05d', $number, 1 + strlen($value), $directoryLength);
                $directoryLength += strlen($value) + 1;
            }
        }

        $directory = implode('', $directory);
        $data = implode(Record::FIELD_TERMINATOR, $data);
        $recordLength = 24 + strlen($directory) + 1 + strlen($data);

        $leader = sprintf('%05d%s%05d%s', $recordLength, substr($this->leader, 5, 7), 25 + strlen($directory), substr($this->leader, 17));

        $record = $leader . $directory . Record::FIELD_TERMINATOR . $data;
        return $record;
    }

    //

    /**
     * Set leader value.
     *
     * @throws InvalidArgumentexception The value of a leader field must be a single character
     *
     * @param  integer $pos
     * @param  string  $value
     * @return void
     */
    private function setLeaderValue ($pos, $value)
    {
        if (strlen($value) != 1) {
            throw new InvalidArgumentException("The value of a leader field must be a single character: {$value}");
        }
        $this->leader[$pos] = $value;
    }

}
