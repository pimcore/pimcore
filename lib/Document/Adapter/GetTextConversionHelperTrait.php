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

namespace Pimcore\Document\Adapter;

use Pimcore\Model\Asset\Document;

trait GetTextConversionHelperTrait
{
    public function getText(?int $page = null, ?Document $asset = null, ?string $path = null): mixed
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        $filename = $path ?: $asset->getFilename();

        // if asset is pdf extract via ghostscript
        if (parent::isFileTypeSupported($filename)) {
            return parent::getText($page, $asset, $path);
        }

        if ($this->isFileTypeSupported($filename)) {
            return parent::convertPdfToText($page, static::getLocalFileFromStream($this->getPdf($asset)));
        }

        return '';
    }
}
