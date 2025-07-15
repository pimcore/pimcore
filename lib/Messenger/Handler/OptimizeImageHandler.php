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

use Pimcore\Image\ImageOptimizerInterface;
use Pimcore\Messenger\OptimizeImageMessage;
use Pimcore\Tool\Storage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class OptimizeImageHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __construct(protected ImageOptimizerInterface $optimizer, protected LoggerInterface $logger)
    {
    }

    public function __invoke(OptimizeImageMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    // @phpstan-ignore-next-line
    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            try {
                $storage = Storage::get('thumbnail');

                $path = $message->getPath();

                if ($storage->fileExists($path)) {
                    $originalFilesize = $storage->fileSize($path);
                    $this->optimizer->optimizeImage($path);

                    $this->logger->debug('Optimized image: '.$path.' saved '.formatBytes($originalFilesize - $storage->fileSize($path)));
                } else {
                    $this->logger->debug('Skip optimizing of '.$path." because it doesn't exist anymore");
                }

                $ack->ack($message);
            } catch (Throwable $e) {
                $ack->nack($e);
            }
        }
    }

    // @phpstan-ignore-next-line
    private function shouldFlush(): bool
    {
        return 100 <= count($this->jobs);
    }
}
