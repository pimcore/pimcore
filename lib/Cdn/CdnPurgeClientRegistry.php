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

namespace Pimcore\Cdn;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * @internal
 */
class CdnPurgeClientRegistry implements PurgeClientInterface
{
    private ?PurgeClientInterface $resolved = null;

    /**
     * @param ContainerInterface $clients PSR-11 service locator keyed by the `provider`
     *                                    tag attribute. Only the selected provider is
     *                                    instantiated, so installs that do not use a CDN
     *                                    pay no construction cost for FastlyPurgeClient
     *                                    (which would otherwise resolve Fastly-specific
     *                                    env vars eagerly).
     */
    public function __construct(
        #[AutowireLocator('pimcore.cdn.purge_client', indexAttribute: 'provider')]
        private readonly ContainerInterface $clients,
        #[Autowire('%env(CDN_PROVIDER)%')]
        private readonly string $provider,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function getClient(): PurgeClientInterface
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        // Empty CDN_PROVIDER maps to 'null' key (NullPurgeClient), not an empty-string key.
        $providerKey = $this->provider === '' ? 'null' : $this->provider;

        if (!$this->clients->has($providerKey)) {
            if ($providerKey !== 'null') {
                $this->logger->warning(
                    'CDN provider "{provider}" is not registered, falling back to NullPurgeClient.',
                    ['provider' => $this->provider]
                );
            }
            $providerKey = 'null';
        }

        return $this->resolved = $this->clients->get($providerKey);
    }

    public function purgeByTag(string $tag): void
    {
        $this->getClient()->purgeByTag($tag);
    }

    public function purgeByTags(array $tags): void
    {
        $this->getClient()->purgeByTags($tags);
    }

    public function purgeByUrl(string $url): void
    {
        $this->getClient()->purgeByUrl($url);
    }
}
