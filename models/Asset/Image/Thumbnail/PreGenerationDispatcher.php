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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\Messenger\GenerateImageThumbnailsMessage;
use Pimcore\Model\Asset;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;
use function sprintf;

/**
 * Queues the named thumbnails listed in `pimcore.assets.image.thumbnails.pre_generate.definitions`
 * for an image whose binary was just saved.
 *
 * Called by Asset::save() itself and not by the asset update task: that task is not queued for
 * every save (a pending task of the same asset holds the update queue lock), so it cannot tell
 * whether the binary it processes was changed.
 *
 * @internal
 */
class PreGenerationDispatcher
{
    /**
     * @param string[] $thumbnailNames
     */
    public function __construct(
        protected MessageBusInterface $messageBus,
        protected LoggerInterface $logger,
        protected array $thumbnailNames = []
    ) {
    }

    public function dispatch(Asset\Image $image): void
    {
        foreach (array_unique($this->thumbnailNames) as $thumbnailName) {
            try {
                $this->messageBus->dispatch(
                    new GenerateImageThumbnailsMessage((int) $image->getId(), $thumbnailName)
                );
            } catch (Throwable $e) {
                // pre-generation is an optimization: the image is saved already and its thumbnails
                // are generated on request, so a failing dispatch must not fail the save
                $this->logger->warning(sprintf(
                    'Could not queue the pre-generation of thumbnail "%s" for image %s: %s',
                    $thumbnailName,
                    $image->getId(),
                    $e->getMessage()
                ), ['exception' => $e]);
            }
        }
    }
}
