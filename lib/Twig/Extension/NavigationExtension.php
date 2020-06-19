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

namespace Pimcore\Twig\Extension;

use Pimcore\Model\Document;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Renderer\RendererInterface;
use Pimcore\Templating\Helper\Navigation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationExtension extends AbstractExtension
{
    /**
     * @var Navigation
     */
    private $navigationHelper;

    /**
     * @param Navigation $navigationHelper
     */
    public function __construct(Navigation $navigationHelper)
    {
        $this->navigationHelper = $navigationHelper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_build_nav', [$this, 'buildNavigation']),
            new TwigFunction('pimcore_render_nav', [$this, 'render'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_nav_renderer', [$this, 'getRenderer']),
        ];
    }

    /**
     * This is essentially the same as the buildNavigation method on the
     * Navigation helper, but without a pageCallback as it does not make
     * sense in Twig context. If you need a more customized navigation
     * with a callback, build the navigation externally, pass it to the
     * view and just call render through the extension.
     *
     * @param mixed $params config array or active document (legacy mode)
     * @param Document|null $navigationRootDocument
     * @param string|null $htmlMenuPrefix
     * @param bool|string $cache
     *
     * @return Container
     *
     * @throws \Exception
     */
    public function buildNavigation(
        $params = null,
        Document $navigationRootDocument = null,
        string $htmlMenuPrefix = null,
        $cache = true
    ): Container {
        if (is_array($params)) {
            // using param configuration
            return $this->navigationHelper->build($params);
        } else {
            // using deprecated argument configuration ($params = navigation root document)
            return $this->navigationHelper->buildNavigation(
                $params,
                $navigationRootDocument,
                $htmlMenuPrefix,
                null,
                $cache
            );
        }
    }

    /**
     * Loads a renderer instance
     *
     * @param string $alias
     *
     * @return RendererInterface
     */
    public function getRenderer(string $alias): RendererInterface
    {
        return $this->navigationHelper->getRenderer($alias);
    }

    /**
     * Renders a navigation with the given renderer
     *
     * @param Container $container
     * @param string $rendererName
     * @param string|null $renderMethod     Optional render method to use (e.g. menu -> renderMenu)
     * @param array<int, mixed> $rendererArguments      Option arguments to pass to the render method after the container
     *
     * @return string
     */
    public function render(
        Container $container,
        string $rendererName = 'menu',
        string $renderMethod = null,
        ...$rendererArguments
    ) {
        return call_user_func_array([$this->navigationHelper, 'render'], func_get_args());
    }
}
