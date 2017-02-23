<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener\Frontend;

use Pimcore\Bundle\PimcoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolverAwareInterface;

abstract class AbstractFrontendListener implements PimcoreContextResolverAwareInterface
{
    use PimcoreContextAwareTrait;
}
