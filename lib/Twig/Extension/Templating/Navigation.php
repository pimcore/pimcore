<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Twig\Extension\Templating;

use Exception;
use InvalidArgumentException;
use Pimcore\Navigation\Builder;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Renderer\Breadcrumbs;
use Pimcore\Navigation\Renderer\Menu as MenuRenderer;
use Pimcore\Navigation\Renderer\RendererInterface;
use Pimcore\Twig\Extension\Templating\Navigation\Exception\InvalidRendererException;
use Pimcore\Twig\Extension\Templating\Navigation\Exception\RendererNotFoundException;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Psr\Container\ContainerInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @method MenuRenderer menu()
 * @method Breadcrumbs breadcrumbs()
 *
 */
class Navigation implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    private Builder $builder;

    private ContainerInterface $rendererLocator;

    public function __construct(Builder $builder, ContainerInterface $rendererLocator)
    {
        $this->builder = $builder;
        $this->rendererLocator = $rendererLocator;
    }

    /**
     * Builds a navigation container by passing params
     * Possible config params are: 'root', 'htmlMenuPrefix', 'pageCallback', 'cache', 'cacheLifetime', 'maxDepth', 'active', 'markActiveTrail'
     *
     *
     *
     * @throws Exception
     */
    public function build(array $params): Container
    {
        return $this->builder->getNavigation($params);
    }

    /**
     * Get a named renderer
     *
     *
     */
    public function getRenderer(string $alias): RendererInterface
    {
        if (!$this->rendererLocator->has($alias)) {
            throw RendererNotFoundException::create($alias);
        }

        $renderer = $this->rendererLocator->get($alias);

        if (!$renderer instanceof RendererInterface) {
            throw InvalidRendererException::create($alias, $renderer);
        }

        return $renderer;
    }

    /**
     * Renders a navigation with the given renderer
     *
     * @param string $renderMethod     Optional render method to use (e.g. menu -> renderMenu)
     * @param array<int, mixed> $rendererArguments      Option arguments to pass to the render method after the container
     *
     */
    public function render(
        Container $container,
        string $rendererName = 'menu',
        string $renderMethod = 'render',
        ...$rendererArguments
    ): string {
        $renderer = $this->getRenderer($rendererName);

        if (!method_exists($renderer, $renderMethod)) {
            throw new InvalidArgumentException(sprintf('Method "%s" does not exist on renderer "%s"', $renderMethod, $rendererName));
        }

        $args = array_merge([$container], array_values($rendererArguments));

        return call_user_func_array([$renderer, $renderMethod], $args);
    }

    /**
     * Magic overload is an alias to getRenderer()
     *
     *
     */
    public function __call(string $method, array $arguments = []): RendererInterface
    {
        return $this->getRenderer($method);
    }
}
