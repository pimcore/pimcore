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
