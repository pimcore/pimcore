<?php

namespace TestSuite\Pimcore\Cache\Core;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

abstract class ArrayCoreHandlerTest extends AbstractCoreHandlerTest
{
    protected function setupItemPool()
    {
        $this->itemPool   = new ArrayAdapter(3600, false);
        $this->tagAdapter = new TagAwareAdapter($this->itemPool);
    }
}
