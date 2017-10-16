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

namespace Pimcore\Analytics\Piwik\EventListener;

use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Piwik\ReportBroker;
use Pimcore\Event\Admin\IndexSettingsEvent;
use Pimcore\Event\AdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IndexSettingsListener implements EventSubscriberInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ReportBroker
     */
    private $reportBroker;

    public function __construct(ConfigProvider $configProvider, ReportBroker $reportBroker)
    {
        $this->configProvider = $configProvider;
        $this->reportBroker   = $reportBroker;
    }

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
        $config = $this->configProvider->getConfig();

        $settings = [
            'iframe_configured' => $config->isIframeIntegrationConfigured(),
            'reports'           => []
        ];

        $reports = $this->reportBroker->getReports();
        if (count($reports) > 0) {
            $piwikReports = [];
            foreach ($reports as $report) {
                $piwikReports[$report->getId()] = [
                    'id'    => $report->getId(),
                    'title' => $report->getTitle()
                ];
            }

            $settings['reports'] = $piwikReports;
        }

        $event->getSettings()->piwik = $settings;
    }
}
