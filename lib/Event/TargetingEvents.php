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
     * Fired when the targeting code is rendered. Allows to add data to the targeting
     * code or to change the template completely.
     *
     * @Event("Pimcore\Event\Targeting\TargetingCodeEvent")
     *
     * @var string
     */
    const TARGETING_CODE = 'pimcore.targeting.targeting_code';

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
     * Fired when a rule matches after all actions were applied
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

    /**
     * Fired when a target group which is configured on document settings
     * is assigned to a visitor info.
     *
     * @Event("Pimcore\Event\Targeting\AssignDocumentTargetGroupEvent")
     *
     * @var string
     */
    const ASSIGN_DOCUMENT_TARGET_GROUP = 'pimcore.targeting.assign_document_target_group';

    /**
     * Fired after a condition was used which depends on the count of visited
     * pages. Will be used by VisitedPagesCountListener to update the page count
     * if there are conditions depending on it.
     *
     * @Event("Symfony\Component\EventDispatcher\Event")
     *
     * @var string
     */
    const VISITED_PAGES_COUNT_MATCH = 'pimcore.targeting.visited_pages_count_match';

    /**
     * Fired before the targeting debug toolbar is rendered
     *
     * @Event("Pimcore\Event\Targeting\RenderToolbarEvent")
     *
     * @var string
     */
    const RENDER_TOOLBAR = 'pimcore.targeting.render_toolbar';
}
