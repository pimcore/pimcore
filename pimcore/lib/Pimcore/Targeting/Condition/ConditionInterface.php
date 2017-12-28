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

interface ConditionInterface
{
    /**
     * Create an instance from a config array
     *
     * @param array $config
     *
     * @return ConditionInterface
     */
    public static function fromConfig(array $config);

    /**
     * Determines if the condition is able to match. E.g. if a country condition
     * does not define a value (= all countries), it does not need to query the
     * data provider for the country name as it would match everything. Returning
     * false here will set the match result implicitely to false.
     *
     * @return bool
     */
    public function canMatch(): bool;

    /**
     * Tests condition against visitor info
     *
     * @param VisitorInfo $visitorInfo
     *
     * @return bool
     */
    public function match(VisitorInfo $visitorInfo): bool;
}
