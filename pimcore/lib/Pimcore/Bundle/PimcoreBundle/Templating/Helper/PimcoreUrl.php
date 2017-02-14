<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

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
        $request = $this->requestHelper->getCurrentRequest();

        // merge all parameters from request to parameters
        if (!$reset) {
            $urlOptions = array_replace($urlOptions, $request->request->all());
        }

        return $this->generateUrl($name, $urlOptions, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
