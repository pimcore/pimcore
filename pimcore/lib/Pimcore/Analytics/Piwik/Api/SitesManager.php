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
use Pimcore\Analytics\SiteConfig\SiteConfig;
use Pimcore\Config as PimcoreConfig;
use Pimcore\File;
use Symfony\Component\Translation\TranslatorInterface;

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

    public function addSite(SiteConfig $siteConfig, array $params = []): int
    {
        $siteId = $this->config->getSiteId($siteConfig->getConfigKey());
        if (null !== $siteId) {
            throw new \LogicException(sprintf(
                'The site "%s" already defines a Piwik site ID. Please use updateSite instead.',
                $siteConfig->getConfigKey()
            ));
        }

        $params = array_merge($this->buildParameters([
            'method'   => 'SitesManager.addSite',
            'siteName' => $siteConfig->getTitle($this->translator),
            'urls'     => $this->buildSiteUrls($siteConfig)
        ]), $params);

        $response = $this->apiClient->request('POST', [
            'query' => $params
        ]);

        $siteId = $response['value'] ?? null;
        if (!$siteId) {
            throw new \RuntimeException('Failed to read site ID from API response');
        }

        $siteId = (int)$siteId;
        if ($siteId < 1) {
            throw new \RuntimeException('Returned site ID is invalid');
        }

        // save site ID to config
        $this->saveSiteId($siteConfig->getConfigKey(), $siteId);

        return $siteId;
    }

    public function updateSite(SiteConfig $siteConfig, array $params = []): int
    {
        $siteId = $this->config->getSiteId($siteConfig->getConfigKey());

        if (null === $siteId) {
            throw new \LogicException(sprintf(
                'The site "%s" does not define a Piwik site ID. Please use createSite instead.',
                $siteConfig->getConfigKey()
            ));
        }

        $currentUrls = $this->getSiteUrlsFromId($siteId);

        $params = array_merge($this->buildParameters([
            'method' => 'SitesManager.updateSite',
            'idSite' => $siteId,
            'urls'   => $this->buildSiteUrls($siteConfig, $currentUrls)
        ]), $params);

        $response = $this->apiClient->request('POST', [
            'query' => $params
        ]);

        if ('success' === $response['result'] && 'ok' === $response['message']) {
            return $siteId;
        }

        throw ApiException::fromResponse('SitesManager.updateSite() request failed', $response);
    }

    public function getSiteFromId(int $siteId, array $params = []): array
    {
        $params = array_merge($this->buildParameters([
            'method' => 'SitesManager.getSiteFromId',
            'idSite' => $siteId
        ]), $params);

        $response = $this->apiClient->get($params);

        if ($response && count($response) === 1) {
            return $response[0];
        }

        throw ApiException::fromResponse(
            'Unexpected API response format. Expected array with a single entry for SitesManager.getSiteFromId',
            $response
        );
    }

    public function getSiteUrlsFromId(int $siteId, array $params = []): array
    {
        $params = array_merge($this->buildParameters([
            'method' => 'SitesManager.getSiteUrlsFromId',
            'idSite' => $siteId
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

    private function buildSiteUrls(SiteConfig $siteConfig, array $urls = []): array
    {
        $siteUrls = [];
        if ($site = $siteConfig->getSite()) {
            if (!empty($site->getMainDomain())) {
                $siteUrls[] = $site->getMainDomain();
            }

            foreach ($site->getDomains() as $domain) {
                if (!empty($domain)) {
                    $siteUrls[] = $domain;
                }
            }
        } elseif (SiteConfig::CONFIG_KEY_MAIN_DOMAIN === $siteConfig->getConfigKey()) {
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
     * @param string $configKey
     * @param int $siteId
     */
    private function saveSiteId(string $configKey, int $siteId)
    {
        $configFile = PimcoreConfig::locateConfigFile('reports.php');

        if (file_exists($configFile)) {
            $config = new PimcoreConfig\Config(include($configFile), true);
        } else {
            $config = new PimcoreConfig\Config([]);
        }

        $config->piwik->sites->$configKey->site_id = $siteId;

        File::putPhpFile(
            $configFile,
            to_php_data_file_format($config->toArray())
        );
    }
}
