<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Tool\RequestHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper generating URLs through symfony routing
 */
abstract class AbstractrUrlHelper extends AbstractHelper
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(UrlGeneratorInterface $generator, RequestHelper $requestHelper)
    {
        $this->generator     = $generator;
        $this->requestHelper = $requestHelper;
    }

    /**
     * Generate URL with support to only pass parameters ZF1 style (defaults to current route).
     *
     * @param null $name
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    protected function generateUrl($name = null, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        // if name is an array, treat it as parameters
        if (is_array($name)) {
            if (is_array($parameters)) {
                $parameters = array_merge($name, $parameters);
            } else {
                $parameters = $name;
            }

            $name = null;
        }

        // get name from current route
        if ($name === null) {
            $name = $this->requestHelper->getCurrentRequest()->attributes->get('_route');
        }

        return $this->generator->generate($name, $parameters, $referenceType);
    }
}
