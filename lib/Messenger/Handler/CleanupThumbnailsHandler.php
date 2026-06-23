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

use Pimcore\Messenger\CleanupThumbnailsMessage;
use Pimcore\Model\Asset;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class CleanupThumbnailsHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;
    use HandlerHelperTrait;

    public function __invoke(CleanupThumbnailsMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    // @phpstan-ignore-next-line
    private function process(array $jobs): void
    {
        $jobs = $this->filterUnique($jobs, static function (CleanupThumbnailsMessage $message) {
            return $message->getType() . '-' . $message->getName();
        });

        foreach ($jobs as [$message, $ack]) {
            try {
                $configClass = 'Pimcore\Model\Asset\\' . ucfirst($message->getType()) . '\Thumbnail\Config';
                /** @var Asset\Image\Thumbnail\Config|Asset\Video\Thumbnail\Config $thumbConfig */
                $thumbConfig = new $configClass();
                $thumbConfig->setName($message->getName());
                $thumbConfig->clearTempFiles();

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }
}
