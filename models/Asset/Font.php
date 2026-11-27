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

namespace Pimcore\Model\Asset;

use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\Asset\Font\PreviewGenerator;

/**
 * Font files (TrueType, OpenType, WOFF, ...).
 *
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Font extends Model\Asset
{
    protected string $type = 'font';

    protected function update(array $params = []): void
    {
        parent::update($params);

        if ($params['isUpdate']) {
            $this->clearThumbnails();
        }
    }

    /**
     * Returns an image thumbnail of a rendered sample text set in this font.
     *
     * @param bool $deferred deferred means that the image will be generated on-the-fly (when requested by the browser)
     */
    public function getImageThumbnail(
        array|string|Image\Thumbnail\Config $thumbnailName,
        bool $deferred = false
    ): Font\ImageThumbnailInterface {
        if (!$this->isThumbnailsEnabled() || !PreviewGenerator::isAvailable()) {
            return new Font\ImageThumbnail(null);
        }

        return new Font\ImageThumbnail($this, $thumbnailName, $deferred);
    }

    /**
     * The sample text which is rendered for preview images / thumbnails of this font.
     */
    public function getPreviewText(): string
    {
        return (string) $this->getFontConfig()['thumbnails']['preview_text'];
    }

    /**
     * @internal
     */
    public function getPreviewFontSize(): int
    {
        return (int) $this->getFontConfig()['thumbnails']['font_size'];
    }

    /**
     * @internal
     */
    public function isThumbnailsEnabled(): bool
    {
        return (bool) $this->getFontConfig()['thumbnails']['enabled'];
    }

    private function getFontConfig(): array
    {
        return Config::getSystemConfiguration('assets')['font'];
    }
}
