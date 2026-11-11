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

namespace Pimcore\Document;

use Exception;
use Pimcore\Model\Asset;

/**
 * @internal
 */
interface AdapterInterface
{
    public function isAvailable(): bool;

    public function isFileTypeSupported(string $fileType): bool;

    /**
     * @return $this
     */
    public function load(Asset\Document $asset): static;

    public function saveImage(string $imageTargetPath, int $page = 1, int $resolution = 200): bool;

    /**
     * @return resource
     *
     * @throws Exception
     */
    public function getPdf(?Asset\Document $asset = null);

    /**
     * @throws Exception
     */
    public function getPageCount(): int;

    public function getText(?int $page = null, ?Asset\Document $asset = null): mixed;
}
