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

namespace Pimcore\Tracking;

use Pimcore\Model\Site;

interface TrackerInterface
{
    /**
     * Get code for the current site if any/fall back to main domain
     *
     * @param Site|null $site
     *
     * @return null|string Null if no tracking is configured
     */
    public function getCode(Site $site = null);

    /**
     * Get code for main domain
     *
     * @return null|string
     */
    public function getMainCode();

    /**
     * Get code for a specific site
     *
     * @param Site $site
     *
     * @return null|string
     */
    public function getSiteCode(Site $site);

    /**
     * Adds additional code to the tracker
     *
     * @param string $code             The code to add
     * @param string $block            The block where to add the code
     * @param bool $prepend            Whether to prepend the code to the code block
     * @param Site|string|null $config Restrict the part to a specific site (can be either a string like site_1 or
     *                                 default or a Site instance). By default, it will be added to the current site.
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, $config = null);
}
