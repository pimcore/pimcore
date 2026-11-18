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

namespace Pimcore\Bundle\SeoBundle\Redirect;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ElementType;
use Pimcore\Model\Element\Service;
use Throwable;

/**
 * @internal
 */
final class TargetResolver implements TargetResolverInterface
{
    public function isUsableAsTarget(ElementInterface $element): bool
    {
        if ($element instanceof Concrete) {
            try {
                // a data object has no URL of its own - its class' link generator builds one
                return $element->getClass()->getLinkGenerator() !== null;
            } catch (Throwable) {
                // an object whose class definition is gone cannot produce a URL either
                return false;
            }
        }

        return match (true) {
            $element instanceof Asset => !$element instanceof Asset\Folder,
            $element instanceof Document\Page,
            $element instanceof Document\Link,
            $element instanceof Document\Hardlink => true,
            default => false,
        };
    }

    public function resolveElement(string $path, ElementType $targetType): ?ResolvedTarget
    {
        $element = Service::getElementByPath($targetType->value, $path);

        if ($element === null || !$this->isUsableAsTarget($element)) {
            return null;
        }

        return new ResolvedTarget((int) $element->getId(), $targetType);
    }

    public function detectElement(string $path): ?ResolvedTarget
    {
        foreach (ElementType::cases() as $candidate) {
            $resolved = $this->resolveElement($path, $candidate);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    public function resolveTargetElement(string $target, ?ElementType $targetType = null): ?ElementInterface
    {
        if (!is_numeric($target)) {
            return null;
        }

        return Service::getElementById(($targetType ?? ElementType::Document)->value, (int) $target);
    }

    public function resolvePath(string $target, ?ElementType $targetType = null): ?string
    {
        return $this->resolveTargetElement($target, $targetType)?->getRealFullPath();
    }
}
