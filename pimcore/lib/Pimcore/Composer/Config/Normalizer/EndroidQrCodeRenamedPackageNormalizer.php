<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Composer\Config\Normalizer;

use Pimcore\Composer\Config\NormalizerInterface;

/**
 * Makes sure only endroid/qr-code is in the list of dependencies (was renamed from the
 * obsolete endroid/qrcode).
 */
class EndroidQrCodeRenamedPackageNormalizer implements NormalizerInterface
{
    public function normalize(array $config): array
    {
        if (!isset($config['require']['endroid/qrcode'])) {
            return $config;
        }

        // only the old package exists -> add the new one before removing the old one
        if (!isset($config['require']['endroid/qr-code'])) {
            $config['require']['endroid/qr-code'] = $config['require']['endroid/qrcode'];
        }

        unset($config['require']['endroid/qrcode']);

        return $config;
    }
}
