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

namespace Pimcore\FeatureToggles;

/**
 * Lazily loads a state if no state is set. When a feature toggle state is requested and no
 * value is registered, the feature manager will delegate initialization to its registered
 * initializers to provide a default state. As soon as there is a state for a specific feature,
 * initializers won't be called anymore.
 */
interface FeatureStateInitializerInterface
{
    public function getType(): string;

    /**
     * @param FeatureContextInterface $context
     * @param FeatureStateInterface|null $previousState
     *
     * @return FeatureStateInterface|null
     */
    public function getState(FeatureContextInterface $context, FeatureStateInterface $previousState = null);
}
