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

namespace Pimcore\Video;

/**
 * @internal
 */
interface AdapterInterface
{
    public function isAvailable(): bool;

    /**
     * @return $this
     */
    public function load(string $file, array $options = []): static;

    public function save(): bool;

    public function saveImage(string $file, ?int $timeOffset = null): bool;

    public function destroy(): void;

    public function getDuration(): ?float;

    public function getDimensions(): ?array;

    /**
     * @return $this
     */
    public function setAudioBitrate(int $audioBitrate): static;

    public function getAudioBitrate(): int;

    /**
     * @return $this
     */
    public function setVideoBitrate(int $videoBitrate): static;

    public function getVideoBitrate(): int;

    public function getMedias(): ?array;

    public function setMedias(?array $medias): void;

    /**
     * @return $this
     */
    public function setFormat(string $format): static;

    public function getFormat(): string;

    /**
     * @return $this
     */
    public function setDestinationFile(string $destinationFile): static;

    public function getDestinationFile(): string;

    /**
     * @return $this
     */
    public function setLength(int $length): static;

    public function getLength(): int;

    public function getStorageFile(): string;

    public function setStorageFile(string $storageFile): void;
}
