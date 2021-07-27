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

namespace Pimcore\DataObject\GridColumnConfig;

use Pimcore\Model\Element\ElementInterface;

interface ConfigElementInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param ElementInterface|ElementInterface[] $element
     *
     * @return \stdClass|ResultContainer|null
     */
    public function getLabeledValue($element);
}
