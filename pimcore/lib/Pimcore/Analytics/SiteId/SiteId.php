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

namespace Pimcore\Analytics\SiteId;

use Pimcore\Model\Site;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Represents an analytics site config key which is either just "default" without
 * an associated site or a combination of a site with its config key "site_<siteId>".
 */
final class SiteId
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

    public static function forMainDomain(): SiteId
    {
        return new self(self::CONFIG_KEY_MAIN_DOMAIN);
    }

    public static function forSite(Site $site): SiteId
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

    public function getTitle(TranslatorInterface $translator): string
    {
        $site = $this->site;

        $name = null;

        if (null === $site) {
            return $translator->trans('main_site', [], 'admin');
        }

        if ($site->getMainDomain()) {
            $name = $site->getMainDomain();
        } elseif ($site->getRootDocument()) {
            $name = $site->getRootDocument()->getKey();
        }

        $siteSuffix = sprintf(
            '%s: %d',
            $translator->trans('site', [], 'admin'),
            $site->getId()
        );

        if (empty($name)) {
            $name = $siteSuffix;
        } else {
            $name = sprintf('%s (%s)', $name, $siteSuffix);
        }

        return $name;
    }
}
