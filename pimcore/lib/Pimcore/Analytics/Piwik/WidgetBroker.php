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

use Pimcore\Analytics\Piwik\Api\ApiClient;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Piwik\Dto\WidgetConfig;
use Pimcore\Analytics\Piwik\Dto\WidgetReference;
use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Cache\Core\CoreHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Fetches and caches available widget data (stored by site ID and language) from the
 * Piwik API. This is used to show a list of widgets inside the Pimcore admin and to
 * generate iframe URLs to single widgets.
 */
class WidgetBroker
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var CoreHandlerInterface
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
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $widgets = [];

    public function __construct(
        ConfigProvider $configProvider,
        ApiClient $apiClient,
        CoreHandlerInterface $cache,
        UserLoader $userLoader,
        LoggerInterface $logger,
        array $options = []
    )
    {
        $this->configProvider = $configProvider;
        $this->apiClient      = $apiClient;
        $this->cache          = $cache;
        $this->userLoader     = $userLoader;
        $this->logger         = $logger;

        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'cache'                 => true,
            'cache_interval'        => 'PT3H',
            'exclude_categories'    => [
                'About Piwik'
            ],
            'exclude_subcategories' => [],
        ]);

        $resolver->setAllowedTypes('exclude_categories', 'array');
        $resolver->setAllowedTypes('exclude_subcategories', 'array');
        $resolver->setAllowedTypes('cache', 'bool');
        $resolver->setAllowedTypes('cache_interval', ['string', 'null']);
    }

    /**
     * @param string $configKey
     * @param string|null $locale
     *
     * @return array
     */
    public function getWidgetReferences(string $configKey, string $locale = null): array
    {
        $references = [];
        foreach ($this->getWidgetData($configKey, $locale) as $widgetId => $widget) {
            $references[] = new WidgetReference($widgetId, $this->generateTitle($widget));
        }

        return $references;
    }

    public function getWidgetConfig(string $widgetId, string $configKey, string $locale = null): WidgetConfig
    {
        $config  = $this->loadConfig();
        $locale  = $this->resolveLocale($locale);
        $widgets = $this->getWidgetData($configKey, $locale);

        if (!isset($widgets[$widgetId])) {
            throw new \InvalidArgumentException(sprintf('Widget "%s" was not found.', $widgetId));
        }

        $widget = $widgets[$widgetId];
        $url    = $this->generateWidgetUrl($config, $configKey, $widget, $locale);

        return new WidgetConfig($widgetId, $widget['name'], $this->generateTitle($widget), $url, $widget);
    }

    public function getWidgetData(string $configKey, string $locale = null): array
    {
        $config = $this->loadConfig();
        if (!$config->isSiteConfigured($configKey)) {
            throw new \InvalidArgumentException(sprintf('Site "%s" is not configured', $configKey));
        }

        $locale   = $this->resolveLocale($locale);
        $cacheKey = $this->generateCacheKey($config->getPiwikSiteId($configKey), $locale);

        if (isset($this->widgets[$cacheKey])) {
            return $this->widgets[$cacheKey];
        }

        if ($this->options['cache']) {
            if ($widgets = $this->cache->load($cacheKey)) {
                $this->widgets[$cacheKey] = $widgets;

                return $widgets;
            }
        }

        $widgets = $this->loadWidgets($config, $configKey, $locale);

        $this->widgets[$cacheKey] = $widgets;

        if ($this->options['cache']) {
            // cache for 3h
            $this->cache->save(
                $cacheKey,
                $widgets,
                ['piwik.widgets'],
                new \DateInterval($this->options['cache_interval']),
                0,
                true
            );
        }

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

    private function loadWidgets(Config $config, string $configKey, string $locale = null): array
    {
        $data = $this->loadFromApi($config, $configKey, $locale);

        $categorizedTree = $this->categorizeWidgets($data);
        $widgets         = $this->flattenCategorizedWidgetTree($categorizedTree);

        return $widgets;
    }

    /**
     * Categorizes widgets into a category/subcategory tree ordered by respective order
     *
     * @param array $data
     *
     * @return array
     */
    private function categorizeWidgets(array $data): array
    {
        $excludeCategories    = $this->options['exclude_categories'] ?? [];
        $excludeSubCategories = $this->options['exclude_subcategories'] ?? [];

        $tree = [];
        foreach ($data as $entry) {
            $categoryId    = $entry['category'] ? $entry['category']['id'] : '_uncategorized';
            $subcategoryId = $entry['subcategory'] ? $entry['subcategory']['id'] : '_uncategorized';

            if (in_array($categoryId, $excludeCategories)) {
                continue;
            }

            if (in_array($subcategoryId, $excludeSubCategories)) {
                continue;
            }

            $categoryOrder    = $entry['category'] ? ($entry['category']['order'] ?? 0) : 0;
            $subcategoryOrder = $entry['subcategory'] ? ($entry['subcategory']['order'] ?? 0) : 0;

            if (!isset($tree[$categoryOrder])) {
                $tree[$categoryOrder] = [];
            }

            if (!isset($tree[$categoryOrder][$subcategoryOrder])) {
                $tree[$categoryOrder][$subcategoryOrder] = [];
            }

            $tree[$categoryOrder][$subcategoryOrder][] = $entry;
        }

        return $tree;
    }

    /**
     * Flattens category tree into a plain widget list indexed by widget ID
     *
     * @param array $tree
     *
     * @return array
     */
    private function flattenCategorizedWidgetTree(array $tree): array
    {
        $categoryOrder = array_keys($tree);
        sort($categoryOrder);

        $widgets = [];
        foreach ($categoryOrder as $categoryIndex) {
            $subcategoryOrder = array_keys($tree[$categoryIndex]);
            sort($subcategoryOrder);

            foreach ($subcategoryOrder as $subcategoryIndex) {
                foreach ($tree[$categoryIndex][$subcategoryIndex] as $widget) {
                    $widgets[$widget['uniqueId']] = $widget;
                }
            }
        }

        return $widgets;
    }

    private function loadFromApi(Config $config, string $configKey, string $locale = null): array
    {
        $params = [
            'module'     => 'API',
            'method'     => 'API.getWidgetMetadata',
            'format'     => 'JSON',
            'idSite'     => $config->getPiwikSiteId($configKey),
            'token_auth' => $config->getReportToken()
        ];

        if (null !== $locale) {
            $params['language'] = $locale;
        }

        return $this->apiClient->get($params);
    }

    private function generateWidgetUrl(Config $config, string $configKey, array $widget, string $locale = null): string
    {
        $params = [
            'module'      => 'Widgetize',
            'action'      => 'iframe',
            'widget'      => 1,
            'period'      => 'day',
            'date'        => 'yesterday',
            'disableLink' => 1,
            'idSite'      => $config->getPiwikSiteId($configKey),
            'token_auth'  => $config->getReportToken()
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

        $url = sprintf(
            '//%s?%s',
            $config->getPiwikUrl(),
            http_build_query($params)
        );

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
