<?php

declare(strict_types=1);

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

use Pimcore\Model\Document;
use Pimcore\Navigation\Builder;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Renderer\Breadcrumbs;
use Pimcore\Navigation\Renderer\Menu;
use Pimcore\Navigation\Renderer\Menu as MenuRenderer;
use Pimcore\Navigation\Renderer\RendererInterface;
use Pimcore\Templating\Helper\Navigation\Exception\InvalidRendererException;
use Pimcore\Templating\Helper\Navigation\Exception\RendererNotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @method MenuRenderer menu()
 * @method Breadcrumbs breadcrumbs()
 */
class Navigation extends Helper
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var array
     */
    private $rendererMap;

    /**
     * @var RendererInterface[]
     */
    private $renderers = [];

    /**
     * @param ContainerInterface $container
     * @param Builder $builder
     * @param array $rendererMap    Map with alias => id mapping for registered renderers
     */
    public function __construct(ContainerInterface $container, Builder $builder, array $rendererMap)
    {
        $this->container   = $container;
        $this->builder     = $builder;
        $this->rendererMap = $rendererMap;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'navigation';
    }

    /**
     * Builds a navigation container
     *
     * @param Document $activeDocument
     * @param Document|null $navigationRootDocument
     * @param string|null $htmlMenuPrefix
     * @param callable|null $pageCallback
     * @param bool|string $cache
     *
     * @return Container
     */
    public function buildNavigation(
        Document $activeDocument,
        Document $navigationRootDocument = null,
        string $htmlMenuPrefix = null,
        callable $pageCallback = null,
        $cache = true
    ): Container
    {
        return $this->builder->getNavigation(
            $activeDocument,
            $navigationRootDocument,
            $htmlMenuPrefix,
            $pageCallback,
            $cache
        );
    }

    /**
     * Get a named renderer
     *
     * @param string $alias
     *
     * @return RendererInterface
     */
    public function getRenderer(string $alias): RendererInterface
    {
        if (isset($this->renderers[$alias])) {
            return $this->renderers[$alias];
        }

        if (isset($this->rendererMap[$alias])) {
            $renderer = $this->container->get($this->rendererMap[$alias]);

            if (!$renderer instanceof RendererInterface) {
                throw InvalidRendererException::create($alias, $renderer);
            }

            $this->renderers[$alias] = $renderer;

            return $renderer;
        } else {
            throw RendererNotFoundException::create($alias);
        }
    }

    /**
     * Renders a navigation with the given renderer
     *
     * @param Container $container
     * @param string $rendererName
     * @param string|null $renderMethod     Optional render method to use (e.g. menu -> renderMenu)
     * @param array $rendererArguments      Option arguments to pass to the render method after the container
     *
     * @return string
     */
    public function render(
        Container $container,
        string $rendererName = 'menu',
        string $renderMethod = 'render',
        ...$rendererArguments
    )
    {
        $renderer = $this->getRenderer($rendererName);

        if (!method_exists($renderer, $renderMethod)) {
            throw new \InvalidArgumentException(sprintf('Method "%s" does not exist on renderer "%s"', $renderMethod, $rendererName));
        }

        $args = array_merge([$container], array_values($rendererArguments));

        return call_user_func_array([$renderer, $renderMethod], $args);
    }

    /**
     * Magic overload is an alias to getRenderer()
     *
     * @param string $method
     * @param array $arguments
     *
     * @return RendererInterface
     */
    public function __call($method, array $arguments = []): RendererInterface
    {
        return $this->getRenderer($method);
    }
}
