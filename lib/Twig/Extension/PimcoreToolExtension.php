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

use Pimcore\Tool\DeviceDetector;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class PimcoreToolExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_supported_locales', [\Pimcore\Tool::class, 'getSupportedLocales']),
            new TwigFunction('pimcore_device', [DeviceDetector::class, 'getInstance'], ['is_safe' => ['html']]),
        ];
    }
}
