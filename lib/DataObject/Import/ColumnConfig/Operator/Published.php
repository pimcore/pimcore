<?php

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

namespace Pimcore\DataObject\Import\ColumnConfig\Operator;

/**
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
class Published extends AbstractOperator
{
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        if (method_exists($target, 'setPublished')) {
            $published = $rowData[$colIndex] ? true : false;
            if (!$published && method_exists($target, 'setOmitMandatoryCheck')) {
                $target->setOmitMandatoryCheck(true);
            }
            $target->setPublished($published);
        }
    }
}
