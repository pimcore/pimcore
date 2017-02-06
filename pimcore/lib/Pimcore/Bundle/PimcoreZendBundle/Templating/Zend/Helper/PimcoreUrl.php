<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PimcoreUrl extends AbstractrUrlHelper
{
    /**
     * @param array $urlOptions
     * @param null $name
     * @param bool $reset
     * @param bool $encode
     * @param bool $relative
     * @return string
     */
    public function __invoke(array $urlOptions = [], $name = null, $reset = false, $encode = true, $relative = false)
    {
        //merge all parameters from request to parameters
        if (!$reset) {
            $urlOptions = array_merge($_GET, $urlOptions);
        }

        return $this->generateUrl($name, $urlOptions, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
