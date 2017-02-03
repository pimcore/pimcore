<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Url extends AbstractrUrlHelper
{
    /**
     * @param $name
     * @param array $parameters
     * @param bool $schemeRelative
     * @return string
     */
    public function __invoke($name, $parameters = [], $schemeRelative = false)
    {
        return $this->generator->generate($name, $parameters, $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
