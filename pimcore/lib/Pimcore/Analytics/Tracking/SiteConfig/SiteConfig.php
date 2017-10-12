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

namespace Pimcore\Analytics\Tracking\SiteConfig;

use Pimcore\Model\Site;

final class SiteConfig
{
    const CONFIG_KEY_MAIN_DOMAIN = 'default';

    /**
     * @var string
     */
    private $configKey;

    /**
     * @var Site|null
     */
    private $site;

    private function __construct(string $configKey, Site $site = null)
    {
        $this->configKey = $configKey;
        $this->site      = $site;
    }

    public static function forMainDomain(): SiteConfig
    {
        return new self(self::CONFIG_KEY_MAIN_DOMAIN);
    }

    public static function forSite(Site $site): SiteConfig
    {
        $configKey = sprintf('site_%s', $site->getId());

        return new self($configKey, $site);
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * @return Site|null
     */
    public function getSite()
    {
        return $this->site;
    }
}
