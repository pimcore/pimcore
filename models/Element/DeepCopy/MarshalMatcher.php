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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\DeepCopy;

use DeepCopy\TypeMatcher\TypeMatcher;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

/**
 * @internal
 */
class MarshalMatcher extends TypeMatcher
{
    /**
     * @var string|null
     */
    private $sourceType;

    /**
     * @var int|null
     */
    private $sourceId;

    /**
     * MarshalMatcher constructor.
     *
     * @param string|null $sourceType
     * @param int|null $sourceId
     */
    public function __construct(?string $sourceType, ?int $sourceId)
    {
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    public function matches($element): bool
    {
        if ($element instanceof ElementInterface) {
            $elementType = Service::getType($element);
            if ($elementType === $this->sourceType && $element->getId() === $this->sourceId) {
                return false;
            }

            return true;
        }

        return false;
    }
}
