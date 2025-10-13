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

use Pimcore\Model\Asset;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * @internal
 */
class AssetHelperExtensions extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('pimcore_asset', static function ($object) {
                return $object instanceof Asset;
            }),
            new TwigTest('pimcore_asset_archive', static function ($object) {
                return $object instanceof Asset\Archive;
            }),
            new TwigTest('pimcore_asset_audio', static function ($object) {
                return $object instanceof Asset\Audio;
            }),
            new TwigTest('pimcore_asset_document', static function ($object) {
                return $object instanceof Asset\Document;
            }),
            new TwigTest('pimcore_asset_folder', static function ($object) {
                return $object instanceof Asset\Folder;
            }),
            new TwigTest('pimcore_asset_image', static function ($object) {
                return $object instanceof Asset\Image;
            }),
            new TwigTest('pimcore_asset_text', static function ($object) {
                return $object instanceof Asset\Text;
            }),
            new TwigTest('pimcore_asset_unknown', static function ($object) {
                return $object instanceof Asset\Unknown;
            }),
            new TwigTest('pimcore_asset_video', static function ($object) {
                return $object instanceof Asset\Video;
            }),
        ];
    }
}
