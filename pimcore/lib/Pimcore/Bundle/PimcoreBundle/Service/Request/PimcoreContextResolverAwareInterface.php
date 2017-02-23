<?php

namespace Pimcore\Bundle\PimcoreBundle\Service\Request;

interface PimcoreContextResolverAwareInterface
{
    /**
     * @param PimcoreContextResolver $contextResolver
     */
    public function setPimcoreContextResolver(PimcoreContextResolver $contextResolver);
}
