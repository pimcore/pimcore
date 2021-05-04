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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pimcore_image_thumbnail', [$this, 'getImageThumbnail'], ['is_safe' => ['html']]),
            new TwigFilter('pimcore_image_thumbnail_html', [$this, 'getImageThumbnailHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_image_thumbnail', [$this, 'getImageThumbnail'], ['is_safe' => ['html']]),
            new TwigFunction('pimcore_image_thumbnail_html', [$this, 'getImageThumbnailHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param Image  $image
     * @param string $thumbnail
     * @param bool   $deferred
     *
     * @return Image\Thumbnail
     */
    public function getImageThumbnail(Image $image, string $thumbnail, bool $deferred = true): Image\Thumbnail
    {
        return $image->getThumbnail($thumbnail, $deferred);
    }

    /**
     * @param Image  $image
     * @param string $thumbnail
     * @param array  $options
     * @param bool   $deferred
     *
     * @return string
     */
    public function getImageThumbnailHtml(
        Image $image,
        string $thumbnail,
        array $options = [],
        bool $deferred = true
    ): string {
        return $this->getImageThumbnail($image, $thumbnail, $deferred)->getHTML($options);
    }
}
