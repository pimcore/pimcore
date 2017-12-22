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
use Symfony\Component\Translation\TranslatorInterface;

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

    public function __construct(
        SiteIdProvider $siteIdProvider,
        Config $config,
        WidgetBroker $widgetBroker,
        TranslatorInterface $translator
    )
    {
        $this->siteIdProvider = $siteIdProvider;
        $this->config         = $config;
        $this->widgetBroker   = $widgetBroker;
        $this->translator     = $translator;
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

            $entries = $this->getEcommerceWidgets($siteConfig);
            if (empty($entries)) {
                continue;
            }

            $reports[] = [
                'id'      => $siteConfig->getConfigKey(),
                'title'   => $siteConfig->getTitle($this->translator),
                'entries' => $entries,
            ];
        }

        return $reports;
    }

    private function getEcommerceWidgets(SiteId $siteConfig): array
    {
        $widgets = $this->widgetBroker->getWidgetData($siteConfig->getConfigKey());

        $whitelist = [
            'widgetEcommerceOverview',
            'widgetEcommercegetEcommerceLog',
            'widgetGoalsgetItemsSku',
            'widgetGoalsgetVisitsUntilConversionforceView1viewDataTabletabledocumentationForGoalsPage1idGoalecommerceOrder' // yes, that's the real name in piwik..
        ];

        $showAsWidgetWhitelist = [
            'widgetEcommerceOverview'
        ];

        $canIframe = $this->config->isIframeIntegrationConfigured();

        $result = [];
        foreach ($whitelist as $widgetId) {
            if (isset($widgets[$widgetId])) {
                $widgetConfig = $this->widgetBroker->getWidgetConfig($widgetId, $siteConfig->getConfigKey(), null, [
                    'period' => 'month'
                ]);

                $widgetData = $widgetConfig->getData();

                $entry = [
                    'title' => $widgetData['subcategory']['name']
                ];

                if ($canIframe) {
                    $entry['type'] = 'iframe';
                    $entry['url']  = $this->generateIframeUrl($siteConfig, $widgetData);
                } elseif (in_array($widgetId, $showAsWidgetWhitelist)) {
                    $entry['type'] = 'widget';
                    $entry['url']  = $widgetConfig->getUrl();
                } else {
                    continue;
                }

                $result[] = $entry;
            }
        }

        return $result;
    }

    private function generateIframeUrl(SiteId $siteConfig, array $widgetData)
    {
        $piwikSiteId = $this->config->getPiwikSiteId($siteConfig->getConfigKey());

        $url = $this->config->generateIframeUrl([
            'idSite' => $piwikSiteId
        ]);

        $hashParams = [
            'idSite'      => $piwikSiteId,
            'period'      => 'month',
            'date'        => 'yesterday',
            'category'    => $widgetData['category']['id'],
            'subcategory' => $widgetData['subcategory']['id'],
        ];

        return sprintf(
            '%s#?%s',
            $url,
            http_build_query($hashParams)
        );
    }
}
