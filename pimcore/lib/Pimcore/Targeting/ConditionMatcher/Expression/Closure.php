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

namespace Pimcore\Targeting\ConditionMatcher\Expression;

class Closure implements ExpressionInterface
{
    /**
     * @var \Closure
     */
    private $closure;

    /**
     * @var bool
     */
    private $negated = false;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function evaluate(): bool
    {
        $closure = $this->closure;
        $result  = (bool)$closure();

        return $result;
    }
}
