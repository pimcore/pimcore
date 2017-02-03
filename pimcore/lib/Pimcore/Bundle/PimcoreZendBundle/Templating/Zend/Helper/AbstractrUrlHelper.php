<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper generating URLs through symfony routing. The methods below were taken from the Twig RoutingExtension.
 *
 * TODO find out how to register Zend View helpers with multiple methods on the service container. This would eliminate
 * the need for 2 invokable helper classes (Path, Url)
 */
abstract class AbstractrUrlHelper extends AbstractHelper
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }
}
