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

namespace Pimcore\Event\Tracking;

final class PiwikTrackingCodeEvents
{
    /**
     * @Event("Pimcore\Event\Tracking\Piwik\CodeSnippetEvent")
     *
     * @var string
     */
    const BEFORE_INIT = 'pimcore.tracking.piwik.code.before_init';

    /**
     * @Event("Pimcore\Event\Tracking\Piwik\CodeSnippetEvent")
     *
     * @var string
     */
    const TRACK = 'pimcore.tracking.piwik.code.track';

    /**
     * @Event("Pimcore\Event\Tracking\Piwik\CodeSnippetEvent")
     *
     * @var string
     */
    const ASYNC_INIT = 'pimcore.tracking.piwik.code.async_init';

    /**
     * @Event("Pimcore\Event\Tracking\Piwik\CodeSnippetEvent")
     *
     * @var string
     */
    const AFTER_ASYNC = 'pimcore.tracking.piwik.code.after_async_init';

    /**
     * @Event("Pimcore\Event\Tracking\Piwik\TrackingDataEvent")
     *
     * @var string
     */
    const TRACKING_DATA = 'pimcore.tracking.piwik.code.tracking_data';
}
