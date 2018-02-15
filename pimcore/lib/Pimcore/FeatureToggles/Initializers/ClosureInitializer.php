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

namespace Pimcore\FeatureToggles\Initializers;

use Pimcore\FeatureToggles\FeatureContextInterface;
use Pimcore\FeatureToggles\FeatureStateInitializerInterface;
use Pimcore\FeatureToggles\FeatureStateInterface;

class ClosureInitializer implements FeatureStateInitializerInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var \Closure
     */
    private $closure;

    public function __construct(string $type, \Closure $closure)
    {
        $this->type    = $type;
        $this->closure = $closure;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getState(FeatureContextInterface $context, FeatureStateInterface $previousState = null)
    {
        return call_user_func($this->closure, $context, $previousState);
    }
}
