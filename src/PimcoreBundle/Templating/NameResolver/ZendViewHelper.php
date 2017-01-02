<?php

namespace PimcoreBundle\Templating\NameResolver;

class ZendViewHelper implements NameResolverInterface
{
    /**
     * Resolve helper name
     *
     * @param $name
     * @return string
     */
    public function resolve($name)
    {
        // wildcard helper for ZF view helpers - all calls prefixed with zend_ will be resolved to zend helper
        $prefix = 'zend_';
        if (substr($name, 0, strlen($prefix)) === $prefix) {
            $name = 'zend';
        }

        return $name;
    }
}
