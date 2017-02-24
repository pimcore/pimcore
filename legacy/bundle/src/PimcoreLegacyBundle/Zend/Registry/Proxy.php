<?php

namespace PimcoreLegacyBundle\Zend\Registry;

class Proxy extends \Zend_Registry implements \ArrayAccess {

    public function offsetExists($offset)
    {
        return \Pimcore\Cache\Runtime::isRegistered($offset);
    }

    public function offsetGet($offset)
    {
        return \Pimcore\Cache\Runtime::get($offset);
    }

    public function offsetSet($offset, $value)
    {
        \Pimcore\Cache\Runtime::set($offset, $value);
    }

    public function offsetUnset($offset)
    {

    }
}

