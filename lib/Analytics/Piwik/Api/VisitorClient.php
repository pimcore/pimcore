<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Analytics\Piwik\Api;

use Pimcore\Analytics\Piwik\Config\Config;

/**
 * @deprecated
 */
class VisitorClient
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var Config
     */
    private $config;

    public function __construct(ApiClient $apiClient, Config $config)
    {
        $this->apiClient = $apiClient;
        $this->config = $config;
    }

    public function getVisitorProfile(int $piwikSiteId, string $visitorId): array
    {
        $result = $this->apiClient->get($this->buildParameters([
            'method' => 'Live.getVisitorProfile',
            'idSite' => $piwikSiteId,
            'visitorId' => $visitorId,
        ]));

        return $result;
    }

    private function buildParameters(array $parameters): array
    {
        $token = $this->config->getReportToken();
        if (null === $token) {
            throw new \LogicException('Piwik report token is not configured');
        }

        return array_merge([
            'module' => 'API',
            'format' => 'JSON',
            'token_auth' => $token,
        ], $parameters);
    }
}
