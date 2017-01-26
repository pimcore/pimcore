<?php

namespace TestSuite\Pimcore\Cache\Pool\Traits;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use TestSuite\Pimcore\Cache\Traits\LogHandlerTrait;

trait SymfonyProxyTestTrait
{
    use LogHandlerTrait;

    protected $defaultLifetime = 0;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::setupLogger((new \ReflectionClass(__CLASS__))->getShortName());
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::handleLogOutput();
    }

    /**
     * @param SymfonyAdapterProxyCacheItemPool|null $itemPool
     * @return TagAwareAdapterInterface
     */
    protected function getTagAwareAdapter(SymfonyAdapterProxyCacheItemPool $itemPool = null)
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
