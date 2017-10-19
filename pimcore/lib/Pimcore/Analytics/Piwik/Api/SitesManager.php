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

namespace Pimcore\Analytics\Piwik\Api;

use Pimcore\Analytics\Piwik\Api\Exception\ApiException;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Config as PimcoreConfig;
use Pimcore\File;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Exposes parts of the Piwik SitesManager API
 */
class SitesManager
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        ConfigProvider $configProvider,
        ApiClient $apiClient,
        TranslatorInterface $translator
    )
    {
        $this->config     = $configProvider->getConfig();
        $this->apiClient  = $apiClient;
        $this->translator = $translator;
    }

    public function addSite(SiteId $siteId, array $params = []): int
    {
        $piwikSiteId = $this->config->getPiwikSiteId($siteId->getConfigKey());
        if (null !== $piwikSiteId) {
            throw new \LogicException(sprintf(
                'The site "%s" already defines a Piwik site ID. Please use updateSite instead.',
                $siteId->getConfigKey()
            ));
        }

        $params = array_merge($this->buildParameters([
            'method'   => 'SitesManager.addSite',
            'siteName' => $siteId->getTitle($this->translator),
            'urls'     => $this->buildSiteUrls($siteId)
        ]), $params);

        $response = $this->apiClient->request('POST', [
            'query' => $params
        ]);

        $piwikSiteId = $response['value'] ?? null;
        if (!$piwikSiteId) {
            throw new \RuntimeException('Failed to read Piwik site ID from API response');
        }

        $piwikSiteId = (int)$piwikSiteId;
        if ($piwikSiteId < 1) {
            throw new \RuntimeException('Returned Piwik site ID is invalid');
        }

        // save site ID to config
        $this->savePiwikSiteId($siteId, $piwikSiteId);

        return $piwikSiteId;
    }

    public function updateSite(SiteId $siteId, array $params = []): int
    {
        $piwikSiteId = $this->config->getPiwikSiteId($siteId->getConfigKey());

        if (null === $piwikSiteId) {
            throw new \LogicException(sprintf(
                'The site "%s" does not define a Piwik site ID. Please use createSite instead.',
                $siteId->getConfigKey()
            ));
        }

        $currentUrls = $this->getSiteUrlsFromId($piwikSiteId);

        $params = array_merge($this->buildParameters([
            'method' => 'SitesManager.updateSite',
            'idSite' => $piwikSiteId,
            'urls'   => $this->buildSiteUrls($siteId, $currentUrls)
        ]), $params);

        $response = $this->apiClient->request('POST', [
            'query' => $params
        ]);

        if ('success' === $response['result'] && 'ok' === $response['message']) {
            return $piwikSiteId;
        }

        throw ApiException::fromResponse('SitesManager.updateSite() request failed', $response);
    }

    public function getSiteFromId(int $piwikSiteId, array $params = []): array
    {
        $params = array_merge($this->buildParameters([
            'method' => 'SitesManager.getSiteFromId',
            'idSite' => $piwikSiteId
        ]), $params);

        $response = $this->apiClient->get($params);

        if ($response && count($response) === 1) {
            return $response[0];
        }

        throw ApiException::fromResponse(
            'Unexpected API response format. Expected array with a single entry for SitesManager.getSiteFromId.',
            $response
        );
    }

    public function getSiteUrlsFromId(int $piwikSiteId, array $params = []): array
    {
        $params = array_merge($this->buildParameters([
            'method' => 'SitesManager.getSiteUrlsFromId',
            'idSite' => $piwikSiteId
        ]), $params);

        return $this->apiClient->get($params);
    }

    private function buildParameters(array $parameters): array
    {
        return array_merge([
            'module'     => 'API',
            'format'     => 'JSON',
            'token_auth' => $this->getApiToken(),
        ], $parameters);
    }

    private function buildSiteUrls(SiteId $siteId, array $urls = []): array
    {
        $siteUrls = [];
        if ($site = $siteId->getSite()) {
            if (!empty($site->getMainDomain())) {
                $siteUrls[] = $site->getMainDomain();
            }

            foreach ($site->getDomains() as $domain) {
                if (!empty($domain)) {
                    $siteUrls[] = $domain;
                }
            }
        } elseif (SiteId::CONFIG_KEY_MAIN_DOMAIN === $siteId->getConfigKey()) {
            $systemConfig = PimcoreConfig::getSystemConfig();

            $mainDomain = $systemConfig->general->domain;
            if (!empty($mainDomain)) {
                $siteUrls[] = $mainDomain;
            }
        }

        // only add urls which are not already in the list
        foreach ($siteUrls as $siteUrl) {
            if (!in_array($siteUrl, $urls)) {
                $urls[] = $siteUrl;
            }
        }

        // API request could return empty URLs - if only sending those
        // back the API will quit with an error expecting at least one URL
        // while an empty array is fine
        $urls = array_filter($urls, function ($url) {
            return !empty($url);
        });

        return $urls;
    }

    private function getApiToken(): string
    {
        $apiToken = $this->config->getApiToken();
        if (null === $apiToken) {
            throw new \LogicException('Piwik API token is not configured');
        }

        return $apiToken;
    }

    /**
     * Save returned site ID back to the config
     *
     * TODO do we really need to do this here?
     *
     * @param SiteId $siteId
     * @param int $piwikSiteId
     */
    private function savePiwikSiteId(SiteId $siteId, int $piwikSiteId)
    {
        $configKey  = $siteId->getConfigKey();
        $configFile = PimcoreConfig::locateConfigFile('reports.php');

        if (file_exists($configFile)) {
            $config = new PimcoreConfig\Config(include($configFile), true);
        } else {
            $config = new PimcoreConfig\Config([]);
        }

        $config->piwik->sites->$configKey->site_id = $piwikSiteId;

        File::putPhpFile(
            $configFile,
            to_php_data_file_format($config->toArray())
        );
    }
}
