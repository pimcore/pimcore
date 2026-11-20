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
 * Asks a worker to generate the variants of one named thumbnail configuration for one image.
 * It deliberately does not identify the binary it was queued for: the worker generates for the
 * binary the image has when the message is consumed, and the message of a newer upload then finds
 * the files in place.
 *
 * @internal
 */
class GenerateImageThumbnailsMessage
{
    public function __construct(
        protected int $id,
        protected string $thumbnailName
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getThumbnailName(): string
    {
        return $this->thumbnailName;
    }
}
