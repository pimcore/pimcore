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

namespace Pimcore\Analytics;

use Pimcore\Analytics\SiteConfig\SiteConfig;

interface TrackerInterface
{
    /**
     * Get code for a specific site. If no site is passed the current site will be
     * automatically resolved.
     *
     * @param SiteConfig|null $siteConfig
     *
     * @return null|string Null if no tracking is configured
     */
    public function getCode(SiteConfig $siteConfig = null);

    /**
     * Adds additional code to the tracker. Code can either be added to all trackers
     * or be restricted to a specific site.
     *
     * @param string $code                The code to add
     * @param string|null $block          The block where to add the code (will use default block if none given)
     * @param bool $prepend               Whether to prepend the code to the code block
     * @param SiteConfig|null $siteConfig Restrict code to a specific site
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, SiteConfig $siteConfig = null);
}
