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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker\Analytics;

use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\Tracker as EcommerceTracker;

use Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\TrackingItemBuilderInterface;
use Pimcore\Bundle\GoogleMarketingBundle\Tracker\TrackerInterface;
use Twig\Environment;

abstract class AbstractAnalyticsTracker extends EcommerceTracker
{
    protected TrackerInterface $tracker;

    /**
     * @internal
     *
     */
    public function __construct(
        TrackingItemBuilderInterface $trackingItemBuilder,
        Environment $twig,
        TrackerInterface $tracker,
        array $options = [],
        array $assortmentTenants = [],
        array $checkoutTenants = []
    ) {
        parent::__construct($trackingItemBuilder, $twig, $options, $assortmentTenants, $checkoutTenants);
        $this->tracker = $tracker;
    }
}
