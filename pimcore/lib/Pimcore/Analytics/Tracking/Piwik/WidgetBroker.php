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

use Pimcore\Analytics\Tracking\Piwik\Config\Config;
use Pimcore\Analytics\Tracking\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Tracking\Piwik\Dto\WidgetConfig;
use Pimcore\Analytics\Tracking\Piwik\Dto\WidgetReference;
use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Http\ClientFactory;
use Psr\Log\LoggerInterface;

class WidgetBroker
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var array
     */
    private $widgets = [];

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var CoreHandler
     */
    private $cache;

    /**
     * @var UserLoader
     */
    private $userLoader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $cacheInterval = 'PT3H';

    public function __construct(
        ConfigProvider $configProvider,
        ClientFactory $clientFactory,
        CoreHandler $cache,
        UserLoader $userLoader,
        LoggerInterface $logger
    )
    {
        $this->configProvider = $configProvider;
        $this->clientFactory  = $clientFactory;
        $this->cache          = $cache;
        $this->userLoader     = $userLoader;
        $this->logger         = $logger;
    }

    public function getWidgets(int $siteId, string $locale = null): array
    {
        $widgets = $this->getWidgetData($siteId, $locale);

        return $widgets['widgets'] ?? [];
    }

    public function getWidgetReferences(int $siteId, string $locale = null): array
    {
        $widgets = $this->getWidgetData($siteId, $locale);

        $references = [];
        foreach ($widgets['order'] as $widgetId) {
            $references[] = new WidgetReference($widgetId, $this->generateTitle($widgets['widgets'][$widgetId]));
        }

        return $references;
    }

    public function getWidgetConfig(string $widgetId, int $siteId, string $locale = null): WidgetConfig
    {
        $config  = $this->loadConfig();
        $locale  = $this->resolveLocale($locale);
        $widgets = $this->getWidgets($siteId, $locale);

        if (!isset($widgets[$widgetId])) {
            throw new \InvalidArgumentException(sprintf('Widget "%s" was not found', $widgetId));
        }

        $widget = $widgets[$widgetId];
        $url    = $this->generateWidgetUrl($config, $widget, $siteId, $locale);

        return new WidgetConfig($widgetId, $widget['name'], $this->generateTitle($widget), $url, $widget);
    }

    private function getWidgetData(int $siteId, string $locale = null)
    {
        $config   = $this->loadConfig();
        $locale   = $this->resolveLocale($locale);
        $cacheKey = $this->generateCacheKey($siteId, $locale);

        if (isset($this->widgets[$cacheKey])) {
            return $this->widgets[$cacheKey];
        }

        if ($widgets = $this->cache->load($cacheKey)) {
            $this->widgets[$cacheKey] = $widgets;

            return $widgets;
        }

        $widgets = $this->loadWidgets($config, $siteId, $locale);

        $this->widgets[$cacheKey] = $widgets;

        // cache for 3h
        $this->cache->save($cacheKey, $widgets, ['piwik.widgets'], new \DateInterval($this->cacheInterval), 0, true);

        return $widgets;
    }

    private function loadConfig(): Config
    {
        $config = $this->configProvider->getConfig();
        if (!$config->isConfigured()) {
            throw new \RuntimeException('Piwik is not configured');
        }

        if (null === $config->getReportToken()) {
            throw new \RuntimeException('The report token is not configured');
        }

        return $config;
    }

    private function resolveLocale(string $locale = null)
    {
        if (null !== $user = $this->userLoader->getUser()) {
            $locale = $user->getLanguage();
        }

        return $locale;
    }

    private function generateCacheKey(int $siteId, string $locale = null)
    {
        $parts = [(string)$siteId];
        if (!empty($locale)) {
            $parts[] = $locale;
        }

        return implode('_', $parts);
    }

    private function loadWidgets(Config $config, int $siteId, string $locale = null)
    {
        $widgets = [
            'widgets' => [],
            'order'   => [],
        ];

        try {
            $data = $this->loadFromApi($config, $siteId, $locale);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return $widgets;
        }

        $order = [];
        foreach ($data as $widget) {
            $widgets['widgets'][$widget['uniqueId']] = $widget;

            if (!isset($order[$widget['order']])) {
                $order[$widget['order']] = [];
            }

            $order[$widget['order']][] = $widget['uniqueId'];
        }

        foreach ($order as $o => $ids) {
            foreach ($ids as $id) {
                $widgets['order'][] = $id;
            }
        }

        return $widgets;
    }

    private function loadFromApi(Config $config, int $siteId, string $locale = null): array
    {
        $client = $this->clientFactory->createClient();

        $params = [
            'module'     => 'API',
            'method'     => 'API.getWidgetMetadata',
            'format'     => 'JSON',
            'idSite'     => $siteId,
            'token_auth' => $config->getReportToken()
        ];

        if (null !== $locale) {
            $params['language'] = $locale;
        }

        $response = $client->get($this->getBaseUrl($config), [
            'query' => $params
        ]);

        $errorPrefix = 'Failed to load Piwik widgets: ';
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException($errorPrefix . $response->getReasonPhrase());
        }

        $json = json_decode($response->getBody()->getContents(), true);

        if (!is_array($json)) {
            throw new \RuntimeException($errorPrefix . 'unexpected format');
        }

        if (isset($json['result']) && 'error' === $json['result']) {
            throw new \RuntimeException($errorPrefix . $json['message']);
        }

        return $json;
    }

    private function generateWidgetUrl(Config $config, array $widget, int $siteId, string $locale = null): string
    {
        $params = [
            'module'      => 'Widgetize',
            'action'      => 'iframe',
            'widget'      => 1,
            'period'      => 'day',
            'date'        => 'yesterday',
            'disableLink' => 1,
            'idSite'     => $siteId,
            'token_auth' => $config->getReportToken()
        ];

        $params['moduleToWidgetize'] = $widget['module'];
        $params['actionToWidgetize'] = $widget['action'];

        $moduleParams = ['module', 'action'];
        foreach ($widget['parameters'] as $key => $value) {
            if (in_array($key, $moduleParams)) {
                continue;
            }

            $params[$key] = $value;
        }

        if (null !== $locale) {
            $params['language'] = $locale;
        }

        $url = $this->getBaseUrl($config);
        $url = $url . '?' . http_build_query($params);

        return $url;
    }

    private function getBaseUrl(Config $config)
    {
        $scheme = 'http'; // TODO add a config for HTTP/HTTPS
        $url    = sprintf('%s://%s', $scheme, $config->getPiwikUrl());

        return $url;
    }

    private function generateTitle(array $widget)
    {
        $title    = [];
        $category = [];

        if ($widget['category'] && !empty($widget['category']['name'])) {
            $category[] = $widget['category']['name'];
        }

        if ($widget['subcategory'] && !empty($widget['subcategory']['name'])) {
            $category[] = $widget['subcategory']['name'];
        }

        if (!empty($category)) {
            $title[] = implode(' - ', $category);
        }

        $title[] = $widget['name'];

        return implode(' / ', $title);
    }
}
