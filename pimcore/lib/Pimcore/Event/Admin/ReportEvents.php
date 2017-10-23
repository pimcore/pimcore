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

namespace Pimcore\Event\Admin;

final class ReportEvents
{
    /**
     * The SAVE_SETTINGS event is triggered when reports settings are saved
     *
     * @Event("Pimcore\Event\Admin\Report\SettingsEvent")
     *
     * @var string
     */
    const SAVE_SETTINGS = 'pimcore.admin.reports.save_settings';
}
