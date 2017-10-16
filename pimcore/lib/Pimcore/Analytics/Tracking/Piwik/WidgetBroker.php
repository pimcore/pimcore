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

use Pimcore\Analytics\Tracking\Piwik\Dto\WidgetConfig;
use Pimcore\Analytics\Tracking\Piwik\Dto\WidgetReference;
use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Config;
use Pimcore\Http\ClientFactory;
use Psr\Log\LoggerInterface;

class WidgetBroker
{
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
        ClientFactory $clientFactory,
        CoreHandler $cache,
        UserLoader $userLoader,
        LoggerInterface $logger
    )
    {
        $this->clientFactory = $clientFactory;
        $this->cache         = $cache;
        $this->userLoader    = $userLoader;
        $this->logger        = $logger;
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
        $config = $this->getConfig();
        if (null === $config) {
            throw new \RuntimeException('Piwik is not configured');
        }

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
        $widgets = [
            'widgets' => [],
            'order'   => [],
        ];

        $config = $this->getConfig();
        if (null === $config) {
            return $widgets;
        }

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

    /**
     * @return Config\Config|null
     */
    private function getConfig()
    {
        $reportConfig = Config::getReportConfig();
        $config       = $reportConfig->piwik;

        if (!$config) {
            return null;
        }

        if (!$config->piwik_url || !$config->auth_token) {
            return null;
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

    private function loadWidgets(Config\Config $config, int $siteId, string $locale = null)
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

    private function loadFromApi(Config\Config $config, int $siteId, string $locale = null): array
    {
        $client = $this->clientFactory->createClient();

        $params = [
            'module'     => 'API',
            'method'     => 'API.getWidgetMetadata',
            'format'     => 'JSON',
            'idSite'     => $siteId,
            'token_auth' => $config->auth_token
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

    private function generateWidgetUrl(Config\Config $config, array $widget, int $siteId, string $locale = null): string
    {
        $params = [
            'module'      => 'Widgetize',
            'action'      => 'iframe',
            'widget'      => 1,
            'period'      => 'day',
            'date'        => 'yesterday',
            'disableLink' => 1,
            'idSite'     => $siteId,
            'token_auth' => $config->auth_token
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

    private function getBaseUrl(Config\Config $config)
    {
        $scheme = 'http'; // TODO
        $url    = sprintf('%s://%s', $scheme, $config->piwik_url);

        return $url;
    }

    private function generateTitle(array $widget)
    {
        $title = [];

        if ($widget['category'] && !empty($widget['category']['name'])) {
            $title[] = $widget['category']['name'];
        }

        if ($widget['subcategory'] && !empty($widget['subcategory']['name'])) {
            $title[] = $widget['subcategory']['name'];
        }

        $title[] = $widget['name'];

        return implode(' - ', $title);
    }
}
