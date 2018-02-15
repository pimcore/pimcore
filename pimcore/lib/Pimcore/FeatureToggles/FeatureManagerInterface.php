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

interface FeatureManagerInterface
{
    public function getContext(): FeatureContextInterface;

    public function isEnabled(Feature $feature): bool;

    public function setState(FeatureStateInterface $state);

    public function hasState(string $type): bool;

    /**
     * @param string $type
     *
     * @return FeatureStateInterface|null
     */
    public function getState(string $type);

    public function clear(string $type = null);
}
