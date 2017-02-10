<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating;

use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Times the time spent to render a template. This is the same class as the core TimedPhpEngine, but extends our custom
 * PHP engine.
 */
class TimedPhpEngine extends PhpEngine
{
    /**
     * @var Stopwatch
     */
    protected $stopwatch;

    /**
     * @param TemplateNameParserInterface $parser    A TemplateNameParserInterface instance
     * @param ContainerInterface          $container A ContainerInterface instance
     * @param LoaderInterface             $loader    A LoaderInterface instance
     * @param Stopwatch                   $stopwatch A Stopwatch instance
     * @param GlobalVariables             $globals   A GlobalVariables instance
     */
    public function __construct(TemplateNameParserInterface $parser, ContainerInterface $container, LoaderInterface $loader, Stopwatch $stopwatch, GlobalVariables $globals = null)
    {
        parent::__construct($parser, $container, $loader, $globals);

        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = array())
    {
        $e = $this->stopwatch->start(sprintf('template.php (%s)', $name), 'template');

        $ret = parent::render($name, $parameters);

        $e->stop();

        return $ret;
    }

    public function template()
    {
        return '';
    }
}
