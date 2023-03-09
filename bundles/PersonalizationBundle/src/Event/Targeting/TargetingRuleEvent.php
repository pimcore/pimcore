<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\PersonalizationBundle\Event\Targeting;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\Rule;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

class TargetingRuleEvent extends TargetingEvent
{
    private Rule $rule;

    public function __construct(VisitorInfo $visitorInfo, Rule $rule)
    {
        parent::__construct($visitorInfo);

        $this->rule = $rule;
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }
}
