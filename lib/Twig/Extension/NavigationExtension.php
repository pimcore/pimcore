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
