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

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig;

use Pimcore\Model\Element\ElementInterface;

interface ConfigElementInterface
{
    public function getLabel(): string;

    /**
     * @param ElementInterface|ElementInterface[] $element
     *
     * @return \stdClass|ResultContainer|null
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null;

    public function getRenderer(): ?string;
}
