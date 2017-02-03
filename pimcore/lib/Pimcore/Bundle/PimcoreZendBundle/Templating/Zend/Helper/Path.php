<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Path extends AbstractrUrlHelper
{
    /**
     * @param $name
     * @param array $parameters
     * @param bool $relative
     * @return string
     */
    public function __invoke($name, $parameters = [], $relative = false)
    {
        return $this->generator->generate($name, $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
