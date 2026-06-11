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

namespace Pimcore\Bundle\CoreBundle\EventListener;

use Pimcore\Cdn\ImageTransformAdapterInterface;
use Pimcore\Cdn\ThumbnailTransformResolver;
use Pimcore\Event\FrontendEvents;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @internal
 *
 * Rewrites eligible image-thumbnail URLs to CDN image-optimizer URLs at render time.
 *
 * Active only when CDN_IMAGE_OPTIMIZER is set. A variant is rewritten only when:
 *  - the asset is a raster image (not a vector/SVG), and
 *  - the asset's source MIME type is in the configured optimizer allowlist
 *    (pimcore.cdn.image_optimizer_source_formats — the formats the optimizer can ingest), and
 *  - the thumbnail config is fully translatable (ThumbnailTransformResolver returns non-null).
 * Otherwise the Pimcore-resolved frontendPath is left untouched (Pimcore generates the thumbnail).
 */
class CdnImageThumbnailUrlListener implements EventSubscriberInterface
{
    /** @var string[] Lowercased source MIME types the optimizer can ingest. */
    private readonly array $sourceFormats;

    public function __construct(
        private readonly ImageTransformAdapterInterface $adapter,
        private readonly ThumbnailTransformResolver $transformResolver,
        #[Autowire('%env(CDN_IMAGE_OPTIMIZER)%')]
        private readonly string $imageOptimizer,
        #[Autowire('%pimcore.cdn.image_optimizer_source_formats%')]
        array $sourceFormats,
    ) {
        // Normalize to lowercase so the allowlist match is case-insensitive on both sides.
        $this->sourceFormats = array_map('strtolower', $sourceFormats);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FrontendEvents::ASSET_IMAGE_THUMBNAIL => 'onThumbnailPath',
        ];
    }

    public function onThumbnailPath(GenericEvent $event): void
    {
        if ($this->imageOptimizer === '') {
            return;
        }

        $asset = $event->getArgument('asset');

        // Media-type guard: only raster images are eligible. Vector graphics (SVG), and any
        // non-image subject, stay on the Pimcore pipeline.
        if (!$asset instanceof Image || $asset->isVectorGraphic()) {
            return;
        }

        // Source-format guard: only rewrite source formats the optimizer can actually ingest.
        // Other rasters (e.g. TIFF, PSD) stay on the Pimcore pipeline.
        if (!in_array(strtolower((string) $asset->getMimeType()), $this->sourceFormats, true)) {
            return;
        }

        $config = $event->getArgument('config');

        if (!$config instanceof Config) {
            return;
        }

        $transform = $this->transformResolver->resolve($config);
        if ($transform === null) {
            // Config uses a transform we cannot faithfully reproduce on the CDN — fall back.
            return;
        }

        // Focal point lives on the asset, not the thumbnail config: Pimcore turns a `cover` crop
        // into a focal-point crop whenever the asset has a focal point set (see Thumbnail\Processor).
        // The resolver only sees the config and cannot know this, so guard it here where the asset
        // is available — the CDN cover fit is center-only and would crop differently.
        if ($transform->fit === 'cover' && $asset->getCustomSetting('focalPointX')) {
            return;
        }

        $originalPath = '/var/assets' . $asset->getRealFullPath();
        $url = $this->adapter->buildUrl($originalPath, $transform);

        // A no-op adapter (e.g. NullImageTransformAdapter, used when CDN_IMAGE_OPTIMIZER is unset
        // or set to an unknown value) returns the original path unchanged — keep the Pimcore-generated
        // thumbnail rather than pointing the URL at the full-size original.
        if ($url !== $originalPath) {
            $event->setArgument('frontendPath', $url);
        }
    }
}
