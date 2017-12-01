<?php

declare(strict_types=1);

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

use Pimcore\Model\Element\ElementInterface;

interface ConfigElementInterface
{
    /**
     * @param ElementInterface $element The original object
     * @param mixed $target             The current target element which initially is the same as the object. every
     *                                  operator can change the target depending on its needs
     * @param array $rowData            The csv record
     * @param int $colIndex             The column index (0 is the first column)
     * @param array $context
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = []);
}
