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

use Pimcore\Model\Element\ElementType;
use Pimcore\Model\Element\Service;

/**
 * @internal
 */
final class TargetResolver implements TargetResolverInterface
{
    public function resolveElement(string $path, ?ElementType $targetType = null): ?ResolvedTarget
    {
        if ('' === $path) {
            return null;
        }

        $candidates = $targetType !== null ? [$targetType] : ElementType::cases();

        foreach ($candidates as $candidate) {
            $element = Service::getElementByPath($candidate->value, $path);

            if ($element !== null) {
                return new ResolvedTarget((int) $element->getId(), $candidate);
            }
        }

        return null;
    }

    public function resolvePath(string $target, ?ElementType $targetType = null): ?string
    {
        if (!is_numeric($target)) {
            return null;
        }

        $element = Service::getElementById(($targetType ?? ElementType::Document)->value, (int) $target);

        return $element?->getRealFullPath();
    }
}
