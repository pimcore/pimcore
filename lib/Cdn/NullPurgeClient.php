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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pimcore.cdn.purge_client', ['provider' => 'null'])]
class NullPurgeClient implements PurgeClientInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function purgeByTag(string $tag): void
    {
        $this->logger->debug('CDN purge is disabled (NullPurgeClient). Tag: {tag}', ['tag' => $tag]);
    }

    public function purgeByTags(array $tags): void
    {
        $this->logger->debug('CDN purge is disabled (NullPurgeClient). Tags: {tags}', ['tags' => implode(', ', $tags)]);
    }

    public function purgeByUrl(string $url): void
    {
        $this->logger->debug('CDN purge is disabled (NullPurgeClient). URL: {url}', ['url' => $url]);
    }
}
