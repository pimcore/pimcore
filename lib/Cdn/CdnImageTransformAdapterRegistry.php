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

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

/**
 * @internal
 */
class CdnImageTransformAdapterRegistry implements ImageTransformAdapterInterface
{
    use CdnServiceLocatorTrait;

    private ?ImageTransformAdapterInterface $resolved = null;

    /**
     * @param ContainerInterface $adapters PSR-11 locator keyed by the `optimizer` tag attribute.
     */
    public function __construct(
        #[AutowireLocator('pimcore.cdn.image_transform_adapter', 'optimizer')]
        private readonly ContainerInterface $adapters,
        #[Autowire('%env(CDN_IMAGE_OPTIMIZER)%')]
        private readonly string $optimizer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function buildUrl(string $originalPath, ThumbnailTransform $transform): string
    {
        return $this->getAdapter()->buildUrl($originalPath, $transform);
    }

    private function getAdapter(): ImageTransformAdapterInterface
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        /** @var ImageTransformAdapterInterface $adapter */
        $adapter = $this->resolveFromLocator(
            $this->adapters,
            $this->optimizer,
            $this->logger,
            'CDN image optimizer "{optimizer}" is not registered, falling back to NullImageTransformAdapter.',
            ['optimizer' => $this->optimizer],
        );

        return $this->resolved = $adapter;
    }
}
