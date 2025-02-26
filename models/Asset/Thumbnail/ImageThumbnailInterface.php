<?php

declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Asset\Thumbnail;

use Exception;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config;

interface ImageThumbnailInterface
{
    /**
     * @return null|resource
     */
    public function getStream();

    public function getPathReference(bool $deferredAllowed = false): array;

    /**
     * @internal
     */
    public function reset(): void;

    public function getWidth(): ?int;

    public function getHeight(): ?int;

    public function getRealWidth(): ?int;

    public function getRealHeight(): ?int;

    public function getDimensions(): array;

    public function getAsset(): Asset;

    public function getConfig(): ?Config;

    public function getMimeType(): string;

    public function getFileExtension(): string;

    public function getFrontendPath(): string;

    /**
     * @internal
     *
     * @throws Exception
     */
    public function getLocalFile(): ?string;

    public function exists(): bool;

    /**
     * @internal
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function existsOnStorage(?array $pathReference = []): bool;

    public static function supportsFormat(string $format): bool;

    public function getFileSize(): ?int;

    /**
     * Returns path for thumbnail image in a given file format
     *
     */
    public function getAsFormat(string $format): static;
}
