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

use Pimcore\Tracking\Code\CodeContainer;
use Pimcore\Tracking\SiteConfig\SiteConfig;
use Pimcore\Tracking\SiteConfig\SiteConfigResolver;

abstract class AbstractTracker implements TrackerInterface
{
    /**
     * @var SiteConfigResolver
     */
    private $siteConfigResolver;

    public function __construct(SiteConfigResolver $siteConfigResolver)
    {
        $this->siteConfigResolver = $siteConfigResolver;
    }

    /**
     * @inheritdoc
     */
    public function getCode(SiteConfig $siteConfig = null)
    {
        if (null === $siteConfig) {
            $siteConfig = $this->siteConfigResolver->getSiteConfig();
        }

        return $this->generateCode($siteConfig);
    }

    /**
     * Generates code for a specific site config
     *
     * @param SiteConfig $siteConfig
     *
     * @return string|null
     */
    abstract protected function generateCode(SiteConfig $siteConfig);

    /**
     * @inheritdoc
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, SiteConfig $siteConfig = null)
    {
        $this->getCodeContainer()->addCodePart($code, $block, $prepend, $siteConfig);
    }

    /**
     * Builds the code container which defines available blocks
     *
     * @return CodeContainer
     */
    abstract protected function getCodeContainer(): CodeContainer;
}
