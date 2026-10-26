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

/**
 * Translates between the element path a user works with and the element ID a redirect stores.
 */
interface TargetResolverInterface
{
    /**
     * Resolves an element path to the ID and type a redirect stores for it.
     *
     * A target type restricts the lookup, which is how a path existing in more than one
     * element tree is disambiguated. Without one the element types are tried in order, so
     * documents - the historical default - win over assets and objects.
     *
     * Returns null when the path belongs to no element, meaning the target is a plain URL
     * and has no element type.
     */
    public function resolveElement(string $path, ?ElementType $targetType = null): ?ResolvedTarget;

    /**
     * Resolves a stored target back to the element path, for display and export.
     *
     * Unlike resolveElement() this cannot detect the type: the same ID exists in every
     * element tree. A missing type therefore means a document, which is what redirects
     * created before target types existed - and those created automatically when a document
     * moves - store.
     *
     * Returns null when the target is not an element reference or the element is gone, so
     * callers can fall back to the stored value.
     */
    public function resolvePath(string $target, ?ElementType $targetType = null): ?string;
}
