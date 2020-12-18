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

namespace Pimcore\Twig\Extension\Templating;

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Navigation.php
use Pimcore\Model\Document;
use Pimcore\Navigation\Builder;
use Pimcore\Navigation\Container;
use Pimcore\Navigation\Renderer\Breadcrumbs;
use Pimcore\Navigation\Renderer\Menu;
use Pimcore\Navigation\Renderer\Menu as MenuRenderer;
use Pimcore\Navigation\Renderer\RendererInterface;
use Pimcore\Twig\Extension\Templating\Navigation\Exception\InvalidRendererException;
use Pimcore\Twig\Extension\Templating\Navigation\Exception\RendererNotFoundException;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @method MenuRenderer menu()
 * @method Breadcrumbs breadcrumbs()
 *
 */
class Navigation implements RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ContainerInterface
     */
    private $rendererLocator;

    /**
     * @param Builder $builder
     * @param ContainerInterface $rendererLocator
     */
    public function __construct(Builder $builder, ContainerInterface $rendererLocator)
    {
        $this->builder = $builder;
        $this->rendererLocator = $rendererLocator;
    }

    /**
     * Builds a navigation container by passing arguments
     *
     * @deprecated
     *
     * @param Document $activeDocument
     * @param Document|null $navigationRootDocument
     * @param string|null $htmlMenuPrefix
     * @param callable|null $pageCallback
     * @param bool|string $cache
     * @param int|null $maxDepth
     * @param int|null $cacheLifetime
     *
     * @return Container
     *
     * @throws \Exception
     */
    public function buildNavigation(
        Document $activeDocument,
        Document $navigationRootDocument = null,
        string $htmlMenuPrefix = null,
        callable $pageCallback = null,
        $cache = true,
        $maxDepth = null,
        $cacheLifetime = null
    ): Container {
        return $this->builder->getNavigation(
            $activeDocument,
            $navigationRootDocument,
            $htmlMenuPrefix,
            $pageCallback,
            $cache,
            $maxDepth,
            $cacheLifetime
        );
    }
========
@trigger_error(
    'Pimcore\Templating\Helper\Navigation is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Navigation::class . ' instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Twig\Extension\Templating\Navigation::class);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Navigation.php

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Navigation
     */
    class Navigation extends \Pimcore\Twig\Extension\Templating\Navigation {

    }
}
