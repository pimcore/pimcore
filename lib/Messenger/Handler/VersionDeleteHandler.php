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

namespace Pimcore\Messenger\Handler;

use Exception;
use Pimcore\Logger;
use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Model\Version;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class VersionDeleteHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __invoke(VersionDeleteMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    // @phpstan-ignore-next-line
    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            try {
                $versions = new Version\Listing();
                if ($message->getMaxVersionId() !== null) {
                    // only delete versions that existed when the element was deleted - the id may
                    // have been re-used since (e.g. WebDAV delete-log restore), and versions
                    // created after that restore must survive this deferred cleanup
                    $versions->setCondition('cid = :cid AND ctype = :ctype AND id <= :maxVersionId', [
                        'cid' => $message->getElementId(),
                        'ctype' => $message->getElementType(),
                        'maxVersionId' => $message->getMaxVersionId(),
                    ]);
                } else {
                    $versions->setCondition('cid = :cid AND ctype = :ctype', [
                        'cid' => $message->getElementId(),
                        'ctype' => $message->getElementType(),
                    ]);
                }

                foreach ($versions as $version) {
                    try {
                        $version->delete();
                    } catch (Exception $e) {
                        Logger::err(sprintf('Problem deleting the version with Id: %s, reason: %s', $version->getId(), $e->getMessage()));
                    }
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }
}
