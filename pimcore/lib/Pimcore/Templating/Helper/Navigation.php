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

namespace Pimcore\Templating\Helper;

use Pimcore\Navigation\Builder;
use Pimcore\Navigation\Container;
use Pimcore\Templating\Helper\Navigation\Renderer\Breadcrumbs;
use Pimcore\Templating\Helper\Navigation\Renderer\Menu;
use Pimcore\Templating\Helper\Navigation\Renderer\Menu as MenuRenderer;
use Pimcore\Templating\TimedPhpEngine;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @method MenuRenderer menu()
 * @method Breadcrumbs breadcrumbs()
 */
class Navigation extends Helper
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $defaultRenderer = "menu";

    /**
     * @var array
     */
    protected $renderer = [];

    /**
     * @var PhpEngine
     */
    protected $templatingEngine;

    /**
     * Navigation constructor.
     * @param PhpEngine $templatingEngine
     */
    public function __construct(PhpEngine $templatingEngine)
    {
        $this->templatingEngine = $templatingEngine;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'navigation';
    }

    /**
     * @return Builder
     */
    protected function getBuilder()
    {
        if (!$this->builder) {
            $this->builder = new Builder();
        }

        return $this->builder;
    }

    /**
     * @param null $activeDocument
     * @param null $navigationRootDocument
     * @param null $htmlMenuIdPrefix
     * @param null $pageCallback
     * @param bool $cache
     * @return $this
     */
    public function __invoke($activeDocument = null, $navigationRootDocument = null, $htmlMenuIdPrefix = null, $pageCallback = null, $cache = true)
    {
        // this is the new more convenient way of creating a navigation
        $navContainer = $this->getBuilder()->getNavigation($activeDocument, $navigationRootDocument, $htmlMenuIdPrefix, $pageCallback, $cache);
        $this->setContainer($navContainer);

        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getRenderer($name)
    {
        if (!isset($this->renderer[$name])) {
            $renderClass = 'Pimcore\Templating\Helper\Navigation\Renderer\\' . ucfirst($name);
            if (class_exists($renderClass)) {
                $this->renderer[$name] = new $renderClass;
                $this->renderer[$name]->setHelper($this);
            } else {
                $this->renderer[$name] = false;
            }
        }

        return $this->renderer[$name];
    }

    /**
     * @param Container|null $container
     * @return mixed
     */
    public function render(Container $container = null)
    {
        $helper = $this->getRenderer($this->defaultRenderer);

        return $helper->render($container);
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return PhpEngine
     */
    public function getTemplatingEngine()
    {
        return $this->templatingEngine;
    }

    /**
     * @param TimedPhpEngine $templatingEngine
     */
    public function setTemplatingEngine($templatingEngine)
    {
        $this->templatingEngine = $templatingEngine;
    }

    /**
     * @param $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, array $arguments = [])
    {
        // check if call should proxy to another helper
        if ($helper = $this->getRenderer($method)) {
            return call_user_func_array([$helper, $method], $arguments);
        }

        // default behaviour: proxy call to container
        return call_user_func_array([$this->getContainer(), $method], $arguments);
    }
}
