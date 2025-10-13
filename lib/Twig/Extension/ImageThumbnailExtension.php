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

namespace Pimcore\Twig\Extension;

use Pimcore\Model\Asset\Image;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
class ImageThumbnailExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('pimcore_image_thumbnail', [$this, 'getImageThumbnail'], ['is_safe' => ['html']]),
            new TwigFilter('pimcore_image_thumbnail_html', [$this, 'getImageThumbnailHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_image_thumbnail', [$this, 'getImageThumbnail'], ['is_safe' => ['html']]),
            new TwigFunction('pimcore_image_thumbnail_html', [$this, 'getImageThumbnailHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function getImageThumbnail(Image $image, string $thumbnail, bool $deferred = true): Image\ThumbnailInterface
    {
        return $image->getThumbnail($thumbnail, $deferred);
    }

    public function getImageThumbnailHtml(
        Image $image,
        string $thumbnail,
        array $options = [],
        bool $deferred = true
    ): string {
        return $this->getImageThumbnail($image, $thumbnail, $deferred)->getHTML($options);
    }
}
