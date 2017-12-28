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

use Pimcore\Targeting\Condition\ConditionInterface;

interface ConditionFactoryInterface
{
    /**
     * Builds a condition instance from a config array as configured
     * in the admin UI and stored to DB.
     *
     * @param array $config
     *
     * @return ConditionInterface
     */
    public function build(array $config): ConditionInterface;
}
