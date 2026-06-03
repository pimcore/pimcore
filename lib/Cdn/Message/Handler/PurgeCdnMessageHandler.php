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

namespace Pimcore\Cdn\Message\Handler;

use Pimcore\Cdn\Message\PurgeCdnTagMessage;
use Pimcore\Cdn\Message\PurgeCdnUrlMessage;
use Pimcore\Cdn\PurgeClientInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
class PurgeCdnMessageHandler
{
    public function __construct(private readonly PurgeClientInterface $purgeClient)
    {
    }

    public function __invoke(PurgeCdnTagMessage|PurgeCdnUrlMessage $message): void
    {
        match (true) {
            $message instanceof PurgeCdnTagMessage => $this->purgeClient->purgeByTag($message->tag),
            $message instanceof PurgeCdnUrlMessage => $this->purgeClient->purgeByUrl($message->url),
        };
    }
}
