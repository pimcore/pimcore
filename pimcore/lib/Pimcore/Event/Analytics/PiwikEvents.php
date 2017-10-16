<?php
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

final class PiwikEvents
{
    /**
     * Triggered before a tracking code block is rendered. Can be used to add additional code
     * snippets to the tracking block.
     *
     * @Event("Pimcore\Analytics\Piwik\Event\TrackingDataEvent")
     *
     * @var string
     */
    const CODE_TRACKING_DATA = 'pimcore.tracking.piwik.code.tracking_data';

    /**
     * Triggered when the available Piwik reports are generated. Can be used to add additional reports (iframes) to the
     * report panel.
     *
     * @Event("Pimcore\Analytics\Piwik\Event\ReportConfigEvent")
     *
     * @var string
     */
    const GENERATE_REPORTS = 'pimcore.tracking.piwik.reports.generate';
}
