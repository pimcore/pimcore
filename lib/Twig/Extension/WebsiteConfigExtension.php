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

namespace Pimcore\Twig\Extension;

use Pimcore\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WebsiteConfigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('pimcore_website_config', [$this, 'getWebsiteConfig']),
        ];
    }

    /**
     * Returns website config for the current site
     *
     * @param null|mixed $key       Config key to directly load. If null, the whole config will be returned
     * @param null|mixed $default   Default value to use if the key is not set
     * @param null|mixed $language  Language to use
     *
     * @return Config\Config|mixed
     */
    public function getWebsiteConfig($key = null, $default = null, $language = null)
    {
        return Config::getWebsiteConfigValue($key, $default, $language);
    }
}
