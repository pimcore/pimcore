<?php

namespace TestSuite\Pimcore\Cache\Core;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class ArrayCoreHandlerTest extends AbstractCoreHandlerTest
{
    protected function setUpCacheAdapters()
    {
        $this->cacheAdapter = new ArrayAdapter(3600, false);
        $this->tagAdapter   = new TagAwareAdapter($this->cacheAdapter);
    }
}
