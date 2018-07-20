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

namespace Pimcore\Targeting\Condition;

interface VariableConditionInterface
{
    /**
     * Returns variables which are evaluated in the "Session with Variables"
     * rule scope. This is expected to return the variables which were fetched
     * in the last evaluation run. Each condition is a dedicated instance and
     * can return the variables which were resolved during matching.
     *
     * It's important to store/return these variables in a deterministic way (e.g. same
     * array key order) as the hash of their serialized contents is compared against
     * a stored hash to determine if the rule actions need to be evaluated.
     *
     * @return array
     */
    public function getMatchedVariables(): array;
}
