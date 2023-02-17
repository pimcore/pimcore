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

use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class TargetingEvent extends Event
{
    protected VisitorInfo $visitorInfo;

    public function __construct(VisitorInfo $visitorInfo)
    {
        $this->visitorInfo = $visitorInfo;
    }

    public function getVisitorInfo(): VisitorInfo
    {
        return $this->visitorInfo;
    }

    public function getRequest(): Request
    {
        return $this->visitorInfo->getRequest();
    }
}
