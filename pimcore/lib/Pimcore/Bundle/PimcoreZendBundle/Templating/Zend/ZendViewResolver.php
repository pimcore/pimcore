<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Zend\View\Renderer\RendererInterface as Renderer;
use Zend\View\Resolver\ResolverInterface;

class ZendViewResolver implements ResolverInterface
{
    /**
     * @var TemplateNameParserInterface
     */
    protected $parser;

    /**
     * @var FileLocatorInterface
     */
    protected $locator;

    /**
     * @param TemplateNameParserInterface $parser
     * @param FileLocatorInterface $locator
     */
    public function __construct(TemplateNameParserInterface $parser, FileLocatorInterface $locator)
    {
        $this->parser  = $parser;
        $this->locator = $locator;
    }

    /**
     * Resolve a template/pattern name to a resource the renderer can consume
     *
     * @param  string $name
     * @param  null|Renderer $renderer
     * @return mixed
     */
    public function resolve($name, Renderer $renderer = null)
    {
        $template = $this->parser->parse($name);

        return $this->locator->locate($template);
    }
}
