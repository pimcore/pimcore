<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class ArrayCoreHandlerTest extends AbstractCoreHandlerTest
{
    protected function setupItemPool()
    {
        $arrayAdapter = new ArrayAdapter(3600, false);
        $tagAdapter   = new TagAwareAdapter($arrayAdapter);

        $this->itemPool = new SymfonyAdapterProxyCacheItemPool($tagAdapter, 3600);
    }
}
