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

namespace Pimcore\Image;

trigger_deprecation('pimcore/pimcore', '11.2', 'The "%s" class is deprecated, use "%s" instead.', Chromium::class, HtmlToImage::class);

if (!class_exists(Chromium::class, false)) {
    class_alias(HtmlToImage::class, Chromium::class);
}

if (false) {
    /**
     * @deprecated since Pimcore 11.2, use HtmlToImage instead
     */
    class Chromium extends HtmlToImage
    {
    }
}
