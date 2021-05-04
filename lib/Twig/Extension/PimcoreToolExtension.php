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

use Pimcore\Tool\DeviceDetector;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class PimcoreToolExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_language_flag', [\Pimcore\Tool::class, 'getLanguageFlagFile']),
            new TwigFunction('pimcore_supported_locales', [\Pimcore\Tool::class, 'getSupportedLocales']),
            new TwigFunction('pimcore_device', [DeviceDetector::class, 'getInstance'], ['is_safe' => ['html']]),
        ];
    }
}
