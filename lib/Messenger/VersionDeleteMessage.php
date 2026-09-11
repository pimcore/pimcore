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

namespace Pimcore\Messenger;

/**
 * @internal
 */
class VersionDeleteMessage
{
    /**
     * Not a promoted property: promotion defaults are constructor defaults only, and Messenger
     * deserialization bypasses the constructor - a class-level default is what keeps messages
     * queued by previous releases (which did not carry this field) readable without an
     * uninitialized-property error.
     */
    protected ?int $maxVersionId = null;

    /**
     * @param int|null $maxVersionId highest version id of the element at the time of deletion.
     * The handler only deletes versions up to this bound, so an element id that is re-used after
     * the deletion (e.g. the WebDAV delete-log restore, see Asset\WebDAV\Tree::move()) does not
     * lose versions created after the restore when the queued message is processed later.
     * Pass 0 when the element has no versions at deletion time (nothing to clean up). Null is
     * reserved for messages queued by previous releases and keeps their unbounded behavior.
     */
    public function __construct(
        protected string $elementType,
        protected int $elementId,
        ?int $maxVersionId = null
    ) {
        $this->maxVersionId = $maxVersionId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getMaxVersionId(): ?int
    {
        return $this->maxVersionId;
    }
}
