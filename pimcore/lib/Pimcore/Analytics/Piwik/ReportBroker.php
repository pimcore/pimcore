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

namespace Pimcore\Analytics\Piwik;

use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Piwik\Dto\ReportConfig;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Event\Analytics\PiwikEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Builds a list of all available Piwik reports which should be shown in reports panel. A ReportConfig references an
 * iframe URL with a title. Additional reports can be added by adding them in the GENERATE_REPORTS event.
 */
class ReportBroker
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ReportConfig[]
     */
    private $reports;

    public function __construct(
        ConfigProvider $configProvider,
        SiteIdProvider $siteIdProvider,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->configProvider  = $configProvider;
        $this->siteIdProvider  = $siteIdProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator      = $translator;
    }

    /**
     * @return ReportConfig[]
     */
    public function getReports(): array
    {
        if (null !== $this->reports) {
            return $this->reports;
        }

        $reports = $this->buildReports();

        $event = new Event\ReportConfigEvent($reports);
        $this->eventDispatcher->dispatch(PiwikEvents::GENERATE_REPORTS, $event);

        $this->reports = [];
        foreach ($event->getReports() as $report) {
            $this->reports[$report->getId()] = $report;
        }

        return $this->reports;
    }

    public function getReport(string $id): ReportConfig
    {
        $reports = $this->getReports();

        if (!isset($reports[$id])) {
            throw new \InvalidArgumentException(sprintf('Report "%s" was not found', $id));
        }

        return $reports[$id];
    }

    private function buildReports(): array
    {
        $config = $this->configProvider->getConfig();

        /** @var ReportConfig[] $reports */
        $reports = [];
        if (!$config->isConfigured()) {
            return $reports;
        }

        $reportToken = $config->getReportToken();
        if (empty($reportToken)) {
            return $reports;
        }

        $siteIds        = $this->siteIdProvider->getSiteIds();
        $firstConfigKey = null;

        foreach ($siteIds as $siteId) {
            $configKey = $siteId->getConfigKey();

            if (!$config->isSiteConfigured($configKey)) {
                continue;
            }

            $reports[] = new ReportConfig(
                $configKey,
                $siteId->getTitle($this->translator),
                $this->generateSiteDashboardUrl($config, $configKey)
            );

            if (null === $firstConfigKey) {
                $firstConfigKey = $configKey;
            }
        }

        // add an "all websites report" if any reports were configured
        if (null !== $firstConfigKey) {
            array_unshift($reports, new ReportConfig(
                'all_sites',
                $this->translator->trans('piwik_all_websites_dashboard', [], 'admin'),
                $this->generateAllWebsitesDashboardUrl($config, $firstConfigKey)
            ));
        }

        return $reports;
    }

    private function generateAllWebsitesDashboardUrl(Config $config, string $configKey): string
    {
        $params = [
            'moduleToWidgetize' => 'MultiSites',
            'actionToWidgetize' => 'standalone',
        ];

        return $this->generateDashboardUrl($config, $configKey, $params);
    }

    private function generateSiteDashboardUrl(Config $config, string $configKey): string
    {
        $params = [
            'moduleToWidgetize' => 'Dashboard',
            'actionToWidgetize' => 'index',
        ];

        return $this->generateDashboardUrl($config, $configKey, $params);
    }

    private function generateDashboardUrl(Config $config, string $configKey, array $parameters)
    {
        $parameters = array_merge([
            'module'            => 'Widgetize',
            'action'            => 'iframe',
            'period'            => 'week',
            'date'              => 'yesterday',
            'idSite'            => $config->getPiwikSiteId($configKey),
            'token_auth'        => $config->getReportToken()
        ], $parameters);

        return sprintf(
            '//%s/index.php?%s',
            rtrim($config->getPiwikUrl(), '/'),
            http_build_query($parameters)
        );
    }
}
