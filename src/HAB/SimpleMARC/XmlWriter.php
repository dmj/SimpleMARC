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

/**
 * MARCXML writer.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */
class XmlWriter
{
    /**
     * Return XML representation of record.
     *
     * @param  record $record
     * @return string
     */
    public function write (Record $record)
    {
        $buffer = array();
        $buffer []= '<record xmlns="http://www.loc.gov/MARC21/slim">';
        $buffer []= sprintf('<leader>%s</leader>', self::escape($record->leader()));
        foreach ($record->select('^00[0-9]$') as $shorthand => $values) {
            foreach ($values as $value) {
                $buffer []= sprintf('<controlfield tag="%s">%s</controlfield>', $shorthand, $value);
            }
        }
        foreach ($record->select('^[0-9]{3}/') as $shorthand => $fields) {
            list($tag, $ind) = explode('/', $shorthand);
            foreach ($fields as $subfields) {
                $buffer []= sprintf('<datafield tag="%s" ind1="%s" ind2="%s">', $tag, $ind[0], $ind[1]);
                foreach (self::normalize($subfields) as $subfield) {
                    $buffer []= sprintf('<subfield code="%s">%s</subfield>', $subfield[0], self::escape($subfield[1]));
                }
                $buffer []= '</datafield>';
            }
        }
        $buffer []= '</record>';
        return implode($buffer);
    }

    /**
     * Escape argument for XML output.
     *
     * @param  string $string
     * @return string
     */
    private static function escape ($string)
    {
        return htmlspecialchars($string, ENT_XML1|ENT_DISALLOWED, 'UTF-8');
    }

    /**
     * Normalize subfield list.
     *
     * @param  array $subfields
     * @return array
     */
    private static function normalize (array $subfields)
    {
        $normalized = array();
        foreach ($subfields as $code => $posval) {
            foreach ($posval as $pos => $value) {
                $normalized[$pos] = array($code, $value);
            }
        }
        return $normalized;
    }
}
