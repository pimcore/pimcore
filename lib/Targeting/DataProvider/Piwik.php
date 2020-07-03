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

namespace Pimcore\Targeting\DataProvider;

use Pimcore\Analytics\Piwik\Api\VisitorClient;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Debug\Traits\StopwatchTrait;
use Pimcore\Targeting\Model\VisitorInfo;
use Psr\Log\LoggerInterface;

class Piwik implements DataProviderInterface
{
    use StopwatchTrait;

    const PROVIDER_KEY = 'piwik';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var VisitorClient
     */
    private $visitorClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Config $config,
        SiteIdProvider $siteIdProvider,
        VisitorClient $visitorClient,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->siteIdProvider = $siteIdProvider;
        $this->visitorClient = $visitorClient;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has(self::PROVIDER_KEY)) {
            return;
        }

        $result = null;

        try {
            $this->startStopwatch('Targeting:piwikData', 'targeting');

            $result = $this->loadData($visitorInfo);

            $this->stopStopwatch('Targeting:piwikData');
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        $visitorInfo->set(
            self::PROVIDER_KEY,
            $result
        );
    }

    private function loadData(VisitorInfo $visitorInfo)
    {
        // no visitor ID - nothing to fetch
        if (!$visitorInfo->hasVisitorId()) {
            return null;
        }

        // piwik is not configured properly
        if (!$this->config->isConfigured() || empty($this->config->getReportToken())) {
            return null;
        }

        $siteId = $this->siteIdProvider->getForRequest($visitorInfo->getRequest());
        $piwikSiteId = $this->config->getPiwikSiteId($siteId->getConfigKey());

        // piwik site is not configured -> we can't fetch data
        if (null === $piwikSiteId) {
            return null;
        }

        $this->logger->debug('Fetching Piwik visitor profile for ID {visitorId}', [
            'visitorId' => $visitorInfo->getVisitorId(),
        ]);

        return $this->visitorClient->getVisitorProfile(
            $piwikSiteId,
            $visitorInfo->getVisitorId()
        );
    }
}
