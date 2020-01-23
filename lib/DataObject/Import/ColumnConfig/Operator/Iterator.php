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

class Iterator extends AbstractOperator
{
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        $childs = $this->getChilds();

        if (!$childs) {
            return;
        } else {
            /** @var AbstractConfigElement $child */
            foreach ($childs as $child) {
                $child->process($element, $target, $rowData, $colIndex, $context);
            }
        }
    }
}
