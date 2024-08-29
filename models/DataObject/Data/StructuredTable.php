<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use Exception;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class StructuredTable implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected array $data = [];

    public function __construct(array $data = [])
    {
        if ($data) {
            $this->data = $data;
        }
        $this->markMeDirty();
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        $this->markMeDirty();

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     *
     * @return mixed|void
     *
     * @throws Exception
     */
    public function __call(string $name, array $arguments)
    {
        if (str_starts_with($name, 'get')) {
            $key = strtolower(substr($name, 3, strlen($name) - 3));

            $parts = explode('__', $key);
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

            throw new Exception("Requested data $key not available");
        }

        if (str_starts_with($name, 'set')) {
            $key = strtolower(substr($name, 3, strlen($name) - 3));

            $parts = explode('__', $key);
            if (count($parts) == 2) {
                $row = $parts[0];
                $col = $parts[1];

                if (array_key_exists($row, $this->data)) {
                    $rowArray = $this->data[$row];
                    if (array_key_exists($col, $rowArray)) {
                        $this->data[$row][$col] = $arguments[0];
                        $this->markMeDirty();

                        return;
                    }
                }
            } elseif (array_key_exists($key, $this->data)) {
                throw new Exception('Setting a whole row is not allowed.');
            }

            throw new Exception("Requested data $key not available");
        }
    }

    public function isEmpty(): bool
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

    public function __toString(): string
    {
        $string = '<table>';

        foreach ($this->data as $key => $dataRow) {
            $string .= '<tr>';
            $string .= "<td><strong>$key</strong></td>";

            foreach ($dataRow as $c) {
                $string .= "<td>$c</td>";
            }
            $string .= '</tr>';
        }

        $string .= '</table>';

        return $string;
    }

    public function getHtmlTable(array $rowDefs, array $colDefs): string
    {
        $string = '<table>';

        $string .= '<tr>';
        $string .= '<th><strong></strong></th>';
        foreach ($colDefs as $c) {
            $string .= '<th><strong>' . $c['label'] . '</strong></th>';
        }
        $string .= '</tr>';

        foreach ($rowDefs as $r) {
            $dataRow = $this->data[$r['key']];
            $string .= '<tr>';
            $string .= '<th><strong>' . $r['label'] . '</strong></th>';

            foreach ($dataRow as $c) {
                $string .= "<td>$c</td>";
            }
            $string .= '</tr>';
        }

        $string .= '</table>';

        return $string;
    }
}
