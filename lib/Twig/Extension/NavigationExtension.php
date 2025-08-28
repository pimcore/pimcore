<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Twig\Extension\Templating\Navigation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class NavigationExtension extends AbstractExtension
{
    private Navigation $navigationExtension;

    public function __construct(Navigation $navigationExtension)
    {
        $this->navigationExtension = $navigationExtension;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_build_nav', [$this->navigationExtension, 'build']),
            new TwigFunction('pimcore_render_nav', [$this->navigationExtension, 'render'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('pimcore_nav_renderer', [$this->navigationExtension, 'getRenderer']),
        ];
    }
}
