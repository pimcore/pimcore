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

use Pimcore\Twig\Extension\Templating\Inc;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class SubrequestExtension extends AbstractExtension
{
    protected Inc $incHelper;

    public function __construct(Inc $incHelper)
    {
        $this->incHelper = $incHelper;
    }

    public function getFunctions(): array
    {
        // as runtime extension classes are invokable, we can pass them directly as callable
        return [
            new TwigFunction('pimcore_inc', $this->incHelper, [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
