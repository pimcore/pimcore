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

use Pimcore\Analytics\Code\CodeCollector;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;

abstract class AbstractTracker implements TrackerInterface
{
    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var CodeCollector
     */
    private $codeCollector;

    public function __construct(SiteIdProvider $siteIdProvider)
    {
        $this->siteIdProvider = $siteIdProvider;
    }

    /**
     * @inheritdoc
     */
    public function generateCode(SiteId $siteId = null)
    {
        if (null === $siteId) {
            $siteId = $this->siteIdProvider->getForRequest();
        }

        return $this->buildCode($siteId);
    }

    /**
     * Generates code for a specific site config
     *
     * @param SiteId $siteId
     *
     * @return string|null
     */
    abstract protected function buildCode(SiteId $siteId);

    /**
     * @inheritdoc
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, SiteId $siteId = null)
    {
        $action = $prepend ? CodeCollector::ACTION_PREPEND : CodeCollector::ACTION_APPEND;

        $this->getCodeCollector()->addCodePart($code, $block, $action, $siteId);
    }

    /**
     * Lazy initialize the code collector
     *
     * @return CodeCollector
     */
    protected function getCodeCollector(): CodeCollector
    {
        if (null === $this->codeCollector) {
            $this->codeCollector = $this->buildCodeCollector();
        }

        return $this->codeCollector;
    }

    /**
     * Builds the code collector which allows to add additional content to
     * specific blocks.
     *
     * @return CodeCollector
     */
    abstract protected function buildCodeCollector(): CodeCollector;
}
