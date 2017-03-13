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

namespace Pimcore\Cache\Pool\Redis;

use Pimcore\Cache\Pool\Exception\CacheException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConnectionFactory
{
    const DEFAULT_CONNECT_TIMEOUT = 2.5;
    const DEFAULT_CONNECT_RETRIES = 1;

    /**
     * Create a connection
     *
     * @param array $options
     * @return \Credis_Client
     * @throws CacheException
     */
    public static function createConnection($options = [])
    {
        $resolver = new OptionsResolver();
        static::configureOptions($resolver);

        $options = $resolver->resolve($options);

        if (empty($options['port']) && substr($options['server'], 0, 1) != '/') {
            throw new CacheException('Redis \'port\' not specified.');
        }

        $redis = new \Credis_Client($options['server'], $options['port'], $options['timeout'], $options['persistent']);

        if ($options['force_standalone']) {
            $redis->forceStandalone();
        }

        $redis->setMaxConnectRetries($options['connect_retries']);

        if (!empty($options['read_timeout']) && $options['read_timeout'] > 0) {
            $redis->setReadTimeout($options['read_timeout']);
        }

        if (!empty($options['password'])) {
            if (!$redis->auth($options['password'])) {
                throw new CacheException('Unable to authenticate with the redis server.');
            }
        }

        if (!$redis->select($options['database'])) {
            throw new CacheException('The redis database could not be selected.');
        }

        return $redis;
    }

    /**
     * @param OptionsResolver $resolver
     */
    private static function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('server');
        $resolver->setDefaults([
            'port'             => 6379,
            'timeout'          => static::DEFAULT_CONNECT_TIMEOUT,
            'persistent'       => '',
            'force_standalone' => false,
            'connect_retries'  => static::DEFAULT_CONNECT_RETRIES,
            'read_timeout'     => 0,
            'password'         => null,

            // always select database on startup in case persistent connection is re-used by other code
            'database'         => 0
        ]);

        $resolver->setNormalizer('read_timeout', function (Options $options, $value) {
            return (float)$value;
        });


        $resolver->setNormalizer('database', function (Options $options, $value) {
            return (int)$value;
        });
    }

    final private function __construct()
    {
    }

    final private function __clone()
    {
    }
}
