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

use Pimcore\Targeting\Model\VisitorInfo;

interface VariableConditionInterface
{
    /**
     * Returns variables which are evaluated in the "Session with Variables"
     * rule scope
     *
     * @param VisitorInfo $visitorInfo
     *
     * @return array
     */
    public function getVariables(VisitorInfo $visitorInfo): array;
}
