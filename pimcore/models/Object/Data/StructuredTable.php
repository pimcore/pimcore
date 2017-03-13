<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data;

class StructuredTable
{

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        if ($data) {
            $this->data = $data;
        }
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == "get") {
            $key = strtolower(substr($name, 3, strlen($name)-3));

            $parts = explode("__", $key);
            if (count($parts) == 2) {
                $row = $parts[0];
                $col = $parts[1];

                if (array_key_exists($row, $this->data)) {
                    $rowArray = $this->data[$row];
                    if (array_key_exists($col, $rowArray)) {
                        return $rowArray[$col];
                    }
                }
            } elseif (array_key_exists($key, $this->data)) {
                return $this->data[$key];
            }

            throw new \Exception("Requested data $key not available");
        }


        if (substr($name, 0, 3) == "set") {
            $key = strtolower(substr($name, 3, strlen($name)-3));

            $parts = explode("__", $key);
            if (count($parts) == 2) {
                $row = $parts[0];
                $col = $parts[1];

                if (array_key_exists($row, $this->data)) {
                    $rowArray = $this->data[$row];
                    if (array_key_exists($col, $rowArray)) {
                        $this->data[$row][$col] = $arguments[0];

                        return;
                    }
                }
            } elseif (array_key_exists($key, $this->data)) {
                throw new \Exception("Setting a whole row is not allowed.");
            }

            throw new \Exception("Requested data $key not available");
        }
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        foreach ($this->data as $dataRow) {
            foreach ($dataRow as $col) {
                if (!empty($col)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = "<table>";

        foreach ($this->data as $key => $dataRow) {
            $string .= "<tr>";
            $string .= "<td><strong>$key</strong></td>";

            foreach ($dataRow as $c) {
                $string .= "<td>$c</td>";
            }
            $string .= "</tr>";
        }

        $string .= "</table>";

        return $string;
    }

    /**
     * @param $rowDefs
     * @param $colDefs
     * @return string
     */
    public function getHtmlTable($rowDefs, $colDefs)
    {
        $string = "<table>";

        $string .= "<tr>";
        $string .=  "<th><strong></strong></th>";
        foreach ($colDefs as $c) {
            $string .= "<th><strong>" . $c['label'] . "</strong></th>";
        }
        $string .= "</tr>";

        foreach ($rowDefs as $r) {
            $dataRow = $this->data[$r['key']];
            $string .= "<tr>";
            $string .= "<th><strong>" . $r['label'] . "</strong></th>";

            foreach ($dataRow as $c) {
                $string .= "<td>$c</td>";
            }
            $string .= "</tr>";
        }

        $string .= "</table>";

        return $string;
    }
}
