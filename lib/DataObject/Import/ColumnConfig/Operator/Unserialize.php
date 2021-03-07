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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\Import\ColumnConfig\Operator;

use Pimcore\DataObject\Import\ColumnConfig\AbstractConfigElement;

class Unserialize extends AbstractOperator
{
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        $originalCellData = $rowData[$colIndex];
        $celldata = unserialize($rowData[$colIndex]);
        $rowData[$colIndex] = $celldata;

        $childs = $this->getChilds();

        if (!$childs) {
            return;
        } else {
            for ($i = 0; $i < count($childs); $i++) {
                /** @var AbstractConfigElement $child */
                $child = $childs[$i];
                $child->process($element, $target, $rowData, $colIndex, $context);
            }
        }

        $rowData[$colIndex] = $originalCellData;
    }
}
