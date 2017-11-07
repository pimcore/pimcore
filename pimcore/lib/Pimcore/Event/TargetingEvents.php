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

namespace Pimcore\Event;

final class TargetingEvents
{
    /**
     * Fired when the VisitorInfo object was built for a request before
     * any matching and action handling is applied.
     *
     * @Event("Pimcore\Event\Targeting\BuildVisitorInfoEvent")
     *
     * @var string
     */
    const BUILD_VISITOR_INFO = 'pimcore.targeting.build_visitor_info';

    /**
     * Fired when a targeting condition is about to be built. Allows to
     * build the condition in a custom manner instead of relying on the
     * default factory.
     *
     * @Event("Pimcore\Event\Targeting\BuildConditionEvent")
     *
     * @var string
     */
    const BUILD_CONDITION = 'pimcore.targeting.build_condition';
}
