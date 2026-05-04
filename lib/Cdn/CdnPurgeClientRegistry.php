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

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
class CdnPurgeClientRegistry implements PurgeClientInterface
{
    private ?PurgeClientInterface $resolved = null;

    /**
     * @param iterable<string, PurgeClientInterface> $clients
     */
    public function __construct(
        #[TaggedIterator('pimcore.cdn.purge_client', indexAttribute: 'provider')]
        private readonly iterable $clients,
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

        $clients = iterator_to_array($this->clients);

        // Empty CDN_PROVIDER maps to 'null' key (NullPurgeClient), not an empty-string key.
        $providerKey = $this->provider === '' ? 'null' : $this->provider;

        if (!isset($clients[$providerKey]) && $providerKey !== 'null') {
            $this->logger->warning(
                'CDN provider "{provider}" is not registered, falling back to NullPurgeClient.',
                ['provider' => $this->provider]
            );
        }

        return $this->resolved = $clients[$providerKey] ?? $clients['null'];
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
