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

namespace Pimcore\Tracking\Piwik;

use Pimcore\Config;
use Pimcore\Event\Tracking\Piwik\ReportConfigEvent;
use Pimcore\Event\Tracking\PiwikReportEvents;
use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Builds a list of all available Piwik reports which should be shown in reports panel. A ReportConfig references an
 * iframe URL with a title. Additional reports can be added by adding them in the GENERATE_REPORTS event.
 */
class ReportBroker
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->translator      = $translator;
    }

    /**
     * @return ReportConfig[]
     */
    public function getReports(): array
    {
        $reports = $this->buildReports();

        $event = new ReportConfigEvent($reports);
        $this->eventDispatcher->dispatch(PiwikReportEvents::GENERATE_REPORTS, $event);

        return $event->getReports();
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
            $this->translator->trans('all_sites', [], 'admin'),
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

            $reports[] = new ReportConfig($title, $url);
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
