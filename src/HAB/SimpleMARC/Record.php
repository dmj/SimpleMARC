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
 * @copyright Copyright (c) 2013-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */

namespace HAB\SimpleMARC;

use UnexpectedValueException;

/**
 * The read-only MARC record.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2013-2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */
class Record
{
    /**
     * MARC subfield delimiter.
     *
     * @var string
     */
    const SUBFIELD_DELIMITER = "\x1f";

    /**
     * MARC field terminator.
     *
     * @var string
     */
    const FIELD_TERMINATOR = "\x1e";

    /**
     * MARC record terminator.
     *
     * @var string
     */
    const RECORD_TERMINATOR = "\x1d";

    /**
     * Record data.
     *
     * @var string
     */
    private $data;

    /**
     * Base address of record data.
     *
     * @var integer
     */
    private $base;

    /**
     * Directory.
     *
     * @var array
     */
    private $directory;

    /**
     * MARC leader.
     *
     * @var string
     */
    private $leader;

    /**
     * Field cache.
     *
     * @var array
     */
    private $cache;

    /**
     * Constructor.
     *
     * @param  string $data Record data
     * @return void
     */
    public function __construct ($data)
    {
        $this->data = $data;
    }

    /**
     * Return MARC leader.
     *
     * @return string
     */
    public function leader ()
    {
        if ($this->leader === null) {
            $this->leader = substr($this->data, 0, 24);
        }
        return $this->leader;
    }

    /**
     * Return selected fields.
     *
     * A field selectors is a body of a regular expression that is matched
     * against the field shorthands.
     *
     * @param  string $selector Field selector
     * @return array
     */
    public function select ($selector)
    {
        if ($this->directory === null) {
            $this->createDirectory();
        }
        $selector = '@' . addcslashes($selector, '@') . '@u';
        $shorthands = array_filter(array_keys($this->directory), function ($shorthand) use ($selector) { return preg_match($selector, $shorthand); });
        $fields = array();
        foreach ($shorthands as $shorthand) {
            if (!isset($this->cache[$shorthand])) {
                if (strpos($shorthand, '00') === 0) {
                    // Control field
                    $this->cache[$shorthand] = $this->readControlField($shorthand);
                } else {
                    $this->cache[$shorthand] = $this->readDataField($shorthand);
                }
            }
            $fields[$shorthand] = $this->cache[$shorthand];
        }
        return $fields;
    }

    /// Internal API

    /**
     * Create the MARC directory.
     *
     * @return void
     */
    private function createDirectory ()
    {
        $this->directory = array();
        // "The Base address of data is equal to the sum of the lengths of the
        // Leader and the Directory, including the field terminator character
        // at the end of the Directory."
        $this->base      = (int)substr($this->leader(), 12, 5);
        $dirend = $this->base - 1;

        $cursor = 24;
        while ($cursor < $dirend) {
            $tag = substr($this->data, $cursor, 3);
            $cursor += 3;
            $len = substr($this->data, $cursor, 4);
            $cursor += 4;
            $str = substr($this->data, $cursor, 5) + $this->base;
            $cursor += 5;

            if ($tag[0] == '0') {
                // Control field
                $shorthand  = $tag;
            } else {
                // Data field
                $ind = substr($this->data, $str, 2);
                $shorthand = sprintf('%s/%2s', $tag, $ind);
            }
            if (!isset($this->directory[$shorthand])) {
                $this->directory[$shorthand] = array();
            }
            $this->directory[$shorthand] []= array($str, $str + $len, $len);
        }
    }

    /**
     * Return datafield data.
     *
     * @throws UnexpectedValueException Expected subfield delimiter not found
     *
     * @param  string $shorthand
     * @return array
     */
    private function readDataField ($shorthand)
    {
        $data = array();
        foreach ($this->directory[$shorthand] as $field) {
            list($start, $end, ) = $field;
            $cursor   = $start + 2;
            $subpos   = 0;
            $subfield = array();
            do {
                if ($this->data[$cursor] !== self::SUBFIELD_DELIMITER) {
                    throw new UnexpectedValueExcpetion(
                        sprintf(
                            'Missing subfield delimiter at record position %d, field %s',
                            $cursor, $shorthand
                        )
                    );
                }
                $cursor++;

                $next = strpos($this->data, self::SUBFIELD_DELIMITER, $cursor);
                $code = $this->data[$cursor];
                $cursor++;

                if ($next !== false && $next < $end) {
                    $length = $next - $cursor;
                    $value  = substr($this->data, $cursor, $length);
                    $cursor = $next;
                } else {
                    $length = $end - $cursor - 1;
                    $value  = substr($this->data, $cursor, $length);
                    $cursor = $end;
                }
                $subfield[$code][$subpos] = $value;
                $subpos++;
            } while ($cursor < $end);
            $data []= $subfield;
        }
        return $data;
    }

    /**
     * Return controlfield data.
     *
     * @param  string $shorthand
     * @return array
     */
    private function readControlField ($shorthand)
    {
        $data = array();
        foreach ($this->directory[$shorthand] as $field) {
            list($start, , $length) = $field;
            $data []= substr($this->data, $start, $length - 1);
        }
        return $data;
    }
}
