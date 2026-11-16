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
 * The checksum identifies the binary the message was queued for, so that a message consumed
 * after the binary was replaced again is a no-op.
 *
 * @internal
 */
class GenerateImageThumbnailsMessage
{
    public function __construct(
        protected int $id,
        protected string $thumbnailName,
        protected string $checksum
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

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
