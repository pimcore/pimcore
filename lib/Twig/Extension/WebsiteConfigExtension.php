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

use Pimcore\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class WebsiteConfigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_website_config', [$this, 'getWebsiteConfig']),
        ];
    }

    /**
     * Returns website config for the current site
     *
     * @param string|null $key  Config key to directly load. If null, the whole config will be returned
     * @param mixed $default    Default value to use if the key is not set
     *
     */
    public function getWebsiteConfig(?string $key = null, mixed $default = null, ?string $language = null): mixed
    {
        return Config::getWebsiteConfigValue($key, $default, $language);
    }
}
