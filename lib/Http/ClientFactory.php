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

namespace Pimcore\Http;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pimcore\Config;

/**
 * @internal
 */
class ClientFactory
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function createClient(array $config = []): Client
    {
        $guzzleConfig = [
            RequestOptions::TIMEOUT => 3600,
            RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath(),
        ];

        if (($this->config['httpclient']['adapter'] ?? null) == 'Proxy') {
            $authorization = '';
            if (!empty($this->config['httpclient']['proxy_user'])) {
                $authorization = $this->config['httpclient']['proxy_user'] . ':' . $this->config['httpclient']['proxy_pass'] . '@';
            }

            $protocol = 'tcp';
            if (function_exists('curl_exec')) {
                // this is a workaround for https://github.com/pimcore/pimcore/issues/3835
                $protocol = 'http';
            }

            $proxyUri = $protocol . '://' . $authorization . ($this->config['httpclient']['proxy_host'] ?? '') . ':' . ($this->config['httpclient']['proxy_port'] ?? '');

            $guzzleConfig[RequestOptions::PROXY] = $proxyUri;
        }

        $guzzleConfig = array_merge($guzzleConfig, $config);

        $client = new Client($guzzleConfig);

        return $client;
    }
}
