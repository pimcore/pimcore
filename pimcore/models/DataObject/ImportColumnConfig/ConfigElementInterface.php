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

namespace Pimcore\Model\DataObject\ImportColumnConfig;

interface ConfigElementInterface
{
    /**
     * @param $element the original object
     * @param $target the current target element which initially is the same as the object. every operator can change the target depending on its needs
     * @param $rowData the csv record
     * @param $colIndex the column index (0 is the first column)
     *
     * @return mixed
     */
    public function process($element, &$target, &$rowData, $colIndex, &$context = []);
}
