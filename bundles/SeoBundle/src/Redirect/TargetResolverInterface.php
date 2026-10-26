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

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ElementType;

/**
 * Translates between the element path a user works with and the element ID a redirect stores.
 */
interface TargetResolverInterface
{
    /**
     * Whether a redirect can point at this element, i.e. whether Pimcore serves it under a URL
     * of its own. Folders, snippets, emails and data objects whose class has no link generator
     * cannot, so a redirect to one of them could never produce a response.
     */
    public function isUsableAsTarget(ElementInterface $element): bool;

    /**
     * Resolves an element path to the ID and type a redirect stores for it, looking only in
     * the given element type's tree. Returns null when that tree holds no element usable as a
     * redirect target, meaning the target is a plain URL and has no element type.
     */
    public function resolveElement(string $path, ElementType $targetType): ?ResolvedTarget;

    /**
     * Resolves an element path without being told its type, trying the element types in
     * order - documents, the historical default, win over assets and objects. Element types
     * whose element at that path cannot be a redirect target are passed over.
     *
     * Only for callers that genuinely cannot know the type, such as a client that does not
     * send one. Callers which do know it must use resolveElement(), so that a path existing
     * in more than one tree stays unambiguous.
     */
    public function detectElement(string $path): ?ResolvedTarget;

    /**
     * Resolves a stored target back to the element it references.
     *
     * Unlike detectElement() this cannot detect the type: the same ID exists in every element
     * tree. A missing type therefore means a document, which is what redirects created before
     * target types existed - and those created automatically when a document moves - store.
     *
     * Returns null when the target is not an element reference or the element is gone.
     */
    public function resolveTargetElement(string $target, ?ElementType $targetType = null): ?ElementInterface;

    /**
     * The element path of a stored target, for display and export, or null when the target is
     * not an element reference. Note this is the path in the element tree, not necessarily the
     * URL the redirect emits - a data object's URL comes from its class' link generator.
     */
    public function resolvePath(string $target, ?ElementType $targetType = null): ?string;
}
