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

namespace Pimcore\Analytics\Tracking\Piwik;

use Pimcore\Config;
use Pimcore\Event\Admin\IndexSettingsEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Event\Tracking\Piwik\ReportConfigEvent;
use Pimcore\Event\Tracking\PiwikEvents;
use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Builds a list of all available Piwik reports which should be shown in reports panel. A ReportConfig references an
 * iframe URL with a title. Additional reports can be added by adding them in the GENERATE_REPORTS event.
 */
class ReportBroker implements EventSubscriberInterface
{
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

    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->translator      = $translator;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            AdminEvents::INDEX_SETTINGS => 'addIndexSettings'
        ];
    }

    /**
     * Handles INDEX_SETTINGS event and adds piwik reports to settings
     *
     * @param IndexSettingsEvent $event
     */
    public function addIndexSettings(IndexSettingsEvent $event)
    {
        $reports = $this->getReports();
        if (count($reports) > 0) {
            $piwikReports = [];
            foreach ($reports as $report) {
                $piwikReports[$report->getId()] = [
                    'id'    => $report->getId(),
                    'title' => $report->getTitle()
                ];
            }

            $event->getSettings()->piwik = [
                'reports' => $piwikReports
            ];
        }
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

        $event = new ReportConfigEvent($reports);
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
        $reports      = [];
        $reportConfig = Config::getReportConfig();
        $config       = $reportConfig->piwik;

        if (!$config) {
            return $reports;
        }

        if (!$config->auth_token || !$config->piwik_url) {
            return $reports;
        }

        $authToken = trim((string)$config->auth_token);
        if (empty($authToken)) {
            return $reports;
        }

        $piwikUrl = trim((string)$config->piwik_url);
        if (empty($piwikUrl)) {
            return $reports;
        }

        $reports[] = new ReportConfig(
            'all_sites',
            $this->translator->trans('piwik_all_websites_dashboard', [], 'admin'),
            $this->generateAllWebsitesDashboardUrl($piwikUrl, $authToken)
        );

        $profiles = [
            'default' => [
                'title' => $this->translator->trans('main_site', [], 'admin')
            ]
        ];

        foreach ($this->getSites() as $site) {
            $configKey = sprintf('site_%d', $site->getId());

            $profiles[$configKey] = [
                'site' => $site
            ];
        }

        foreach ($profiles as $configKey => $profile) {
            if (!$config->sites->$configKey || !$config->sites->$configKey->site_id) {
                continue;
            }

            $siteId = (int)((string)$config->sites->$configKey->site_id);
            if ($siteId < 1) {
                continue;
            }

            $title = null;
            if ($profile['title']) {
                $title = $profile['title'];
            } elseif ($profile['site']) {
                $title = $this->getSiteTitle($profile['site']);
            }

            if (null === $title) {
                continue;
            }

            $url = $this->generateSiteDashboardUrl($piwikUrl, $authToken, $siteId);

            $reports[] = new ReportConfig($configKey, $title, $url);
        }

        return $reports;
    }

    private function generateAllWebsitesDashboardUrl(string $piwikUrl, string $authToken): string
    {
        return sprintf(
            '//%s/index.php?module=Widgetize&action=iframe&moduleToWidgetize=MultiSites&actionToWidgetize=standalone&period=week&date=yesterday&idSite=1&token_auth=%s',
            $piwikUrl,
            $authToken
        );
    }

    private function generateSiteDashboardUrl(string $piwikUrl, string $authToken, int $siteId): string
    {
        return sprintf(
            '//%s/index.php?module=Widgetize&action=iframe&moduleToWidgetize=Dashboard&actionToWidgetize=index&period=week&date=yesterday&idSite=%s&token_auth=%s',
            $piwikUrl,
            $siteId,
            $authToken
        );
    }

    /**
     * @return Site[]
     */
    private function getSites(): array
    {
        /** @var Site\Listing|Site\Listing\Dao $sites */
        $sites = new Site\Listing();

        return $sites->load();
    }

    private function getSiteTitle(Site $site): string
    {
        $name = null;
        if ($site->getMainDomain()) {
            $name = $site->getMainDomain();
        } elseif ($site->getRootDocument()) {
            $name = $site->getRootDocument()->getKey();
        }

        $siteSuffix = sprintf(
            '%s: %d',
            $this->translator->trans('site', [], 'admin'),
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
