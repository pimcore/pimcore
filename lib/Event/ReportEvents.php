<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event;

final class ReportEvents
{
    /**
     * The SAVE_SETTINGS event is triggered when reports settings are saved
     *
     * @Event("Pimcore\Event\Report\SettingsEvent")
     *
     * @var string
     */
    const SAVE_SETTINGS = 'pimcore.reports.save_settings';
}
