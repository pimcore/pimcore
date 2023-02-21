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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface EventDispatchingConditionInterface
{
    /**
     * Executed before condition is matched
     */
    public function preMatch(VisitorInfo $visitorInfo, EventDispatcherInterface $eventDispatcher): void;

    /**
     * Executed after condition is matched
     */
    public function postMatch(VisitorInfo $visitorInfo, EventDispatcherInterface $eventDispatcher): void;
}
