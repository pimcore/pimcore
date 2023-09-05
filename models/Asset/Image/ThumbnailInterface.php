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

namespace Pimcore\Model\Asset\Image;

use Pimcore\Model\Asset\Thumbnail\ThumbnailInterface as BaseThumbnailInterface;
use Pimcore\Model\Asset\Thumbnail\ThumbnailMediaInterface;

interface ThumbnailInterface extends BaseThumbnailInterface, ThumbnailMediaInterface
{
    public function getPath(array $args = []): string;

    /**
     * Get generated HTML for displaying the thumbnail image in a HTML document.
     *
     * @param array $options Custom configuration
     */
    public function getHtml(array $options = []): string;

    public function getImageTag(array $options = [], array $removeAttributes = []): string;
}
