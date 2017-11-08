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
     * @Event("Pimcore\Event\Targeting\TargetingEvent")
     *
     * @var string
     */
    const PRE_RESOLVE = 'pimcore.targeting.pre_resolve';

    /**
     * Fired after all targeting rules were matched and applied
     *
     * @Event("Pimcore\Event\Targeting\TargetingEvent")
     *
     * @var string
     */
    const POST_RESOLVE = 'pimcore.targeting.post_resolve';

    /**
     * Fired when a rule matches before any actions are applied
     *
     * @Event("Pimcore\Event\Targeting\TargetingRuleEvent")
     *
     * @var string
     */
    const PRE_RULE_ACTIONS = 'pimcore.targeting.pre_rule_actions';

    /**
     * Fired when a rule matches aftert all actions were applied
     *
     * @Event("Pimcore\Event\Targeting\TargetingRuleEvent")
     *
     * @var string
     */
    const POST_RULE_ACTIONS = 'pimcore.targeting.post_rule_actions';

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
