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

namespace Pimcore\Event\Analytics;

final class GoogleAnalyticsEvents
{
    /**
     * Triggered before a tracking code block is rendered. Can be used to add additional code
     * snippets to the tracking block.
     *
     * @Event("Pimcore\Analytics\Google\Event\TrackingDataEvent")
     *
     * @var string
     */
    const CODE_TRACKING_DATA = 'pimcore.tracking.google.code.tracking_data';
}
