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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Reports\Piwik;

use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\Piwik\WidgetBroker;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PiwikReportsProvider
{
    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var WidgetBroker
     */
    private $widgetBroker;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Widgets taken into consideration for reporting menu
     *
     * @var array
     */
    private $reportingWidgets = [
        'widgetEcommerceOverview',
        'widgetEcommercegetEcommerceLog',
        'widgetGoalsgetItemsSku',
        'widgetGoalsgetVisitsUntilConversionforceView1viewDataTabletabledocumentationForGoalsPage1idGoalecommerceOrder', // yes, that's the real name in piwik...
    ];

    /**
     * Widgets which will be linked as standalone widgets instead of full iframe if iframe integration is not configured
     *
     * @var array
     */
    private $widgetFallbackWidgets = [
         'widgetEcommerceOverview',
    ];

    public function __construct(
        SiteIdProvider $siteIdProvider,
        Config $config,
        WidgetBroker $widgetBroker,
        TranslatorInterface $translator
    ) {
        $this->siteIdProvider = $siteIdProvider;
        $this->config = $config;
        $this->widgetBroker = $widgetBroker;
        $this->translator = $translator;
    }

    public function getReportingWidgets(): array
    {
        return $this->reportingWidgets;
    }

    public function setReportingWidgets(array $reportingWidgets)
    {
        $this->reportingWidgets = $reportingWidgets;
    }

    public function getWidgetFallbackWidgets(): array
    {
        return $this->widgetFallbackWidgets;
    }

    public function setWidgetFallbackWidgets(array $widgetFallbackWidgets)
    {
        $this->widgetFallbackWidgets = $widgetFallbackWidgets;
    }

    public function getPiwikEcommerceReports(): array
    {
        if (empty($this->config->getReportToken())) {
            return [];
        }

        $siteConfigs = $this->siteIdProvider->getSiteIds();

        $reports = [];
        foreach ($siteConfigs as $siteConfig) {
            if (!$this->config->isSiteConfigured($siteConfig->getConfigKey())) {
                continue;
            }

            $entries = $this->loadAvailableReports($siteConfig);
            if (empty($entries)) {
                continue;
            }

            $reports[] = [
                'id' => $siteConfig->getConfigKey(),
                'title' => $siteConfig->getTitle($this->translator),
                'entries' => $entries,
            ];
        }

        return $reports;
    }

    private function loadAvailableReports(SiteId $siteConfig): array
    {
        $widgets = $this->widgetBroker->getWidgetData($siteConfig->getConfigKey());
        $canIframe = $this->config->isIframeIntegrationConfigured();

        $result = [];
        foreach ($this->reportingWidgets as $widgetId) {
            if (isset($widgets[$widgetId])) {
                $widgetConfig = $this->widgetBroker->getWidgetConfig($widgetId, $siteConfig->getConfigKey(), null, [
                    'period' => 'month',
                ]);

                $widgetData = $widgetConfig->getData();

                $entry = [
                    'id' => $widgetId,
                    'title' => $widgetData['subcategory']['name'],
                    'fullTitle' => $this->getFullTitle($widgetData),
                ];

                if ($canIframe) {
                    $entry['type'] = 'iframe';
                    $entry['url'] = $this->generateIframeUrl($siteConfig, $widgetData);
                } elseif (in_array($widgetId, $this->widgetFallbackWidgets)) {
                    $entry['type'] = 'widget';
                    $entry['url'] = $widgetConfig->getUrl();
                } else {
                    continue;
                }

                $result[] = $entry;
            }
        }

        return $result;
    }

    private function getFullTitle(array $widgetData): string
    {
        // avoid adding the category if it is already contained in the subcategory, e.g. avoid
        // generating something like "Ecommerce Ecommerce Log" when subcategory is already named
        // "Ecommerce Log"
        if (0 === strpos($widgetData['subcategory']['name'], $widgetData['category']['name'])) {
            return $widgetData['subcategory']['name'];
        }

        return $widgetData['category']['name'] . ' ' . $widgetData['subcategory']['name'];
    }

    private function generateIframeUrl(SiteId $siteConfig, array $widgetData)
    {
        $piwikSiteId = $this->config->getPiwikSiteId($siteConfig->getConfigKey());

        $url = $this->config->generateIframeUrl([
            'idSite' => $piwikSiteId,
        ]);

        $hashParams = [
            'idSite' => $piwikSiteId,
            'period' => 'month',
            'date' => 'yesterday',
            'category' => $widgetData['category']['id'],
            'subcategory' => $widgetData['subcategory']['id'],
        ];

        return sprintf(
            '%s#?%s',
            $url,
            http_build_query($hashParams)
        );
    }
}
