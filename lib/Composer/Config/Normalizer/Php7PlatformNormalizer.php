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
 * Removes the config.platform.php: 7.0 entry which was shipped with early Pimcore 5
 * versions and leads to Symfony not being updated further than 3.3.6
 */
class Php7PlatformNormalizer implements NormalizerInterface
{
    public function normalize(array $config): array
    {
        if (!(isset($config['config']) && isset($config['config']['platform']) && isset($config['config']['platform']['php']))) {
            return $config;
        }

        if ('7.0' === $config['config']['platform']['php']) {
            unset($config['config']['platform']['php']);
        }

        if (empty($config['config']['platform'])) {
            unset($config['config']['platform']);
        }

        if (empty($config['config'])) {
            unset($config['config']);
        }

        return $config;
    }
}
