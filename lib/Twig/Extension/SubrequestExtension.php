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
