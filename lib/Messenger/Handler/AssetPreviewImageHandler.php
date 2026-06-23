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

use Pimcore\Messenger\AssetPreviewImageMessage;
use Pimcore\Model\Asset;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Throwable;

/**
 * @internal
 */
class AssetPreviewImageHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(AssetPreviewImageMessage $message, ?Acknowledger $ack = null): mixed
    {
        return $this->handle($message, $ack);
    }

    // @phpstan-ignore-next-line
    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            try {
                $asset = Asset::getById($message->getId());

                if ($asset instanceof Asset\Image) {
                    $asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
                } elseif ($asset instanceof Asset\Document) {
                    $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
                } elseif ($asset instanceof Asset\Video) {
                    $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
                } elseif ($asset instanceof Asset\Folder) {
                    $asset->getPreviewImage(true);
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
        return 5 <= count($this->jobs);
    }
}
