<?php

namespace Pimcore\View\Helper;

/**
 * Using the container directly is discouraged - inject your dependencies into your services instead. Use this only if
 * absolutely needed.
 */
class Container extends \Zend_View_Helper_Abstract
{
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function container()
    {
        return \Pimcore::getKernel()->getContainer();
    }
}
