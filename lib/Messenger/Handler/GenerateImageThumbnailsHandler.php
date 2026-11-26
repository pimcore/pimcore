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

use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\GenerateImageThumbnailsMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\VariantResolver;
use Pimcore\Model\Exception\ThumbnailFormatNotSupportedException;
use Pimcore\Model\Exception\ThumbnailMaxScalingFactorException;
use Psr\Log\LoggerInterface;
use function count;
use function sprintf;

/**
 * @internal
 */
class GenerateImageThumbnailsHandler
{
    public function __construct(
        protected LoggerInterface $logger,
        protected LongRunningHelper $longRunningHelper,
        protected VariantResolver $variantResolver,
        protected int $maxVariantsPerConfig
    ) {
    }

    public function __invoke(GenerateImageThumbnailsMessage $message): void
    {
        $image = Asset::getById($message->getId());
        if (!$image instanceof Asset\Image) {
            $this->logger->debug(sprintf('Image with ID %s not found, skipping thumbnail pre-generation', $message->getId()));

            return;
        }

        // always generated for the binary the image has now: a message queued for an older binary
        // warms the current one, and the message of the newer upload then finds the files in place
        $config = Config::getByName($message->getThumbnailName());
        if (!$config) {
            $this->logger->warning(sprintf(
                'Thumbnail configuration "%s" does not exist, skipping its pre-generation for image %s',
                $message->getThumbnailName(),
                $image->getId()
            ));

            return;
        }

        $variants = $this->variantResolver->resolve($config);
        if (count($variants) > $this->maxVariantsPerConfig) {
            $this->logger->notice(sprintf(
                'Thumbnail configuration "%s" has %d variants, pre-generating the first %d only (the others are generated on request)',
                $message->getThumbnailName(),
                count($variants),
                $this->maxVariantsPerConfig
            ));
            $variants = array_slice($variants, 0, $this->maxVariantsPerConfig);
        }

        try {
            foreach ($variants as $variant) {
                try {
                    // an existing thumbnail is reused, a failing generation is logged by the thumbnail itself
                    $image->getThumbnail($variant)->getPath(['deferredAllowed' => false]);
                } catch (ThumbnailFormatNotSupportedException|ThumbnailMaxScalingFactorException $e) {
                    // permanent for this variant: a retry would fail the same way
                    $this->logger->warning(sprintf(
                        'Skipping a variant of thumbnail "%s" for image %s: %s',
                        $message->getThumbnailName(),
                        $image->getId(),
                        $e->getMessage()
                    ));
                }
            }
        } finally {
            $this->longRunningHelper->deleteTemporaryFiles();
        }
    }
}
