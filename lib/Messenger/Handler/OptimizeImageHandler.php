<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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

    public function __invoke(OptimizeImageMessage $message, Acknowledger $ack = null): mixed
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
