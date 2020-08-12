<?php
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

namespace Pimcore\Http;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pimcore\Config;

class ClientFactory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function createClient(array $config = [])
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

            $proxyUri = $protocol . '://' . $authorization . $this->config['httpclient']['proxy_host'] ?? '' . ':' . $this->config['httpclient']['proxy_port'] ?? '';

            $guzzleConfig[RequestOptions::PROXY] = $proxyUri;
        }

        $guzzleConfig = array_merge($guzzleConfig, $config);

        $client = new Client($guzzleConfig);

        return $client;
    }

    /**
     * @deprecated Use the ClientFactory service instead of the static method, to be remove in v7.0
     */
    public static function createHttpClient()
    {
        return \Pimcore::getContainer()->get(ClientFactory::class)->createClient();
    }
}
