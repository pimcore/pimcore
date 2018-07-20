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

namespace Pimcore\Targeting;

use Pimcore\Targeting\Model\VisitorInfo;

interface ConditionMatcherInterface
{
    /**
     * Matches a visitor info against a list of condition configurations (as configured via UI)
     *
     * @param VisitorInfo $visitorInfo
     * @param array $configs
     * @param bool $collectVariables
     *
     * @return bool
     */
    public function match(VisitorInfo $visitorInfo, array $configs, bool $collectVariables = false): bool;

    /**
     * Returns collected variables from last match
     *
     * @return array
     */
    public function getCollectedVariables(): array;
}
