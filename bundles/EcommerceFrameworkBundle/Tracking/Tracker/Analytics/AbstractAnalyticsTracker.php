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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use Pimcore\Analytics\Google\Tracker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker as EcommerceTracker;

abstract class AbstractAnalyticsTracker extends EcommerceTracker
{
    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * @required
     *
     * @internal
     *
     * TODO Pimcore 7 remove this setter and set as constructor dependency!
     *
     * @param Tracker $tracker
     */
    public function setTracker(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }
}
