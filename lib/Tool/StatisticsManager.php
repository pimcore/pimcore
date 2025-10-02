<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tool;

use Exception;
use GuzzleHttp\ClientInterface;
use Pimcore;
use Pimcore\Db;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Site;
use Pimcore\Tool;
use Pimcore\Version;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use function array_keys;
use function array_merge;
use function date_default_timezone_get;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
class StatisticsManager
{
    public const STATISTICS_ENDPOINT = 'https://license.pimcore.com/statistics';

    public function __construct(
        private readonly string $instanceIdentifier,
        private readonly KernelInterface $kernel,
        private readonly array $config,
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly RequestHelper $requestHelper,
    ) {
    }

    public function getRequestSpecificData(?Request $request = null): array
    {
        if (!$request) {
            $request = $this->requestHelper->getMainRequest();
        }

        return [
            'host' => $request->getHost(),
            'kernel_debug' => Pimcore::inDebugMode(),
            'dev_mode' => Pimcore::inDevMode(),
            'environment_name' => $this->kernel->getEnvironment(),
        ];
    }

    public function getInstanceData(): array
    {
        $db = Db::get();

        $tables = $db->fetchAllAssociative('
            SELECT
                TABLE_NAME AS name,
                TABLE_ROWS AS `rows`,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS `size_mb`
            FROM
                information_schema.TABLES
            WHERE
                TABLE_SCHEMA = ?
                AND TABLE_ROWS IS NOT NULL;

        ', [$db->getDatabase()]);

        $mysqlVersion = $db->fetchOne('SELECT VERSION()');

        // Site domains
        $sitesDomains = [];
        $sites = new Site\Listing();
        foreach ($sites->load() as $site) {
            $sitesDomains[] = $site->getMainDomain();
            foreach ($site->getDomains() as $domain) {
                $sitesDomains[] = $domain;
            }
        }

        $data = [
            'instanceIdentifier' => $this->instanceIdentifier,
            'pimcore_platform_version' => Version::getPlatformVersion(),
            'pimcore_major_version' => Version::getMajorVersion(),
            'pimcore_version' => Version::getVersion(),
            'pimcore_git_hash' => Version::getRevision(),
            'system_languages' => Tool::getValidLanguages(),
            'main_domain' => $this->config['general']['domain'],
            'sites_domains' => $sitesDomains,
            'timezone' => $this->config['general']['timezone'] ?: date_default_timezone_get(),
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'bundles' => array_keys($this->kernel->getBundles()),
            'tables' => $tables,
        ];

        return $data;
    }

    public function getData(?Request $request = null): array
    {
        return array_merge($this->getRequestSpecificData($request), $this->getInstanceData());
    }

    public function submit(?Request $request = null): bool
    {
        $success = false;
        $data = $this->getData($request);

        try {
            $response = $this->httpClient->request(
                'POST',
                self::STATISTICS_ENDPOINT,
                [
                    'body' => json_encode($data),
                    'timeout' => 5,
                ]
            );

            $success = ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400);
        } catch (Exception $exception) {
            $this->logger->error(sprintf('Unable to submit statistics to %s', self::STATISTICS_ENDPOINT), [
                'exception' => $exception,
            ]);
        }

        return $success;
    }
}
