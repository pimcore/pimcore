<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;

/**
 * Times the time spent to render a template. This is the same class as the core TimedPhpEngine, but extends our custom
 * PHP engine.
 *
 * @deprecated since 6.8.0 and will be removed in Pimcore 7.
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
    public function render($name, array $parameters = [])
    {
        $e = $this->stopwatch->start(sprintf('template.php (%s)', $name), 'template');

        /** @var TemplateReference $name */
        $ret = parent::render($name, $parameters);

        $e->stop();

        return $ret;
    }
}
