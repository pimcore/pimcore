<?php

namespace Pimcore\Tests\Cache\Pool\SymfonyProxy\Traits;

use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

trait SymfonyProxyTestTrait
{
    /**
     * @param SymfonyAdapterProxy|null $itemPool
     *
     * @return TagAwareAdapterInterface
     */
    protected function getTagAwareAdapter(SymfonyAdapterProxy $itemPool = null)
    {
        if (null === $itemPool) {
            $itemPool = $this->cache;
        }

        $reflector = new \ReflectionClass($itemPool);

        $property = $reflector->getProperty('adapter');
        $property->setAccessible(true);

        return $property->getValue($itemPool);
    }

    /**
     * @param TagAwareAdapterInterface|null $tagAwareAdapter
     *
     * @return AdapterInterface
     */
    protected function getItemsAdapter(TagAwareAdapterInterface $tagAwareAdapter = null)
    {
        if (null === $tagAwareAdapter) {
            $tagAwareAdapter = $this->getTagAwareAdapter();
        }

        $reflector = new \ReflectionClass($tagAwareAdapter);

        $property = $reflector->getProperty('itemsAdapter');
        $property->setAccessible(true);

        return $property->getValue($tagAwareAdapter);
    }
}
