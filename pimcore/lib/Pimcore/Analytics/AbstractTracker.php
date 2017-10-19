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

use Pimcore\Analytics\Code\CodeContainer;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;

abstract class AbstractTracker implements TrackerInterface
{
    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    public function __construct(SiteIdProvider $siteIdProvider)
    {
        $this->siteIdProvider = $siteIdProvider;
    }

    /**
     * @inheritdoc
     */
    public function getCode(SiteId $siteId = null)
    {
        if (null === $siteId) {
            $siteId = $this->siteIdProvider->getForRequest();
        }

        return $this->generateCode($siteId);
    }

    /**
     * Generates code for a specific site config
     *
     * @param SiteId $siteId
     *
     * @return string|null
     */
    abstract protected function generateCode(SiteId $siteId);

    /**
     * @inheritdoc
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, SiteId $siteId = null)
    {
        $this->getCodeContainer()->addCodePart($code, $block, $prepend, $siteId);
    }

    /**
     * Builds the code container which defines available blocks
     *
     * @return CodeContainer
     */
    abstract protected function getCodeContainer(): CodeContainer;
}
