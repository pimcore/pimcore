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

namespace Pimcore\Event\Model\Asset;

use Pimcore\Model\Asset;
use Symfony\Contracts\EventDispatcher\Event;

class ResolveMimeTypeEvent extends Event
{
    public function __construct(
        protected string $filename,
        protected string $mimeType,
        protected ?Asset $asset = null,
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }
}
