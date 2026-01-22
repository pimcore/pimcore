<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
    private ?string $sourceType = null;

    private ?int $sourceId = null;

    /**
     * MarshalMatcher constructor.
     *
     */
    public function __construct(?string $sourceType, ?int $sourceId)
    {
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
    }

    /**
     * @param mixed $element
     *
     */
    public function matches($element): bool
    {
        if ($element instanceof ElementInterface) {
            $elementType = Service::getElementType($element);
            if ($elementType === $this->sourceType && $element->getId() === $this->sourceId) {
                return false;
            }

            return true;
        }

        return false;
    }
}
