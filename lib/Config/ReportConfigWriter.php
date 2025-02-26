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

namespace Pimcore\Config;

use Exception;
use Pimcore\Event\Report\SettingsEvent;
use Pimcore\Event\ReportEvents;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles writing/merging report config and emitting an event on config save.
 *
 * @internal
 */
final class ReportConfigWriter
{
    const REPORT_SETTING_ID = 'reports';

    const REPORT_SETTING_SCOPE = 'pimcore';

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws Exception
     */
    public function write(array $settings): void
    {
        $settingsEvent = new SettingsEvent($settings);
        $this->eventDispatcher->dispatch(
            $settingsEvent,
            ReportEvents::SAVE_SETTINGS
        );

        $settings = $settingsEvent->getSettings();

        SettingsStore::set(
            self::REPORT_SETTING_ID,
            json_encode($settings),
            SettingsStore::TYPE_STRING,
            self::REPORT_SETTING_SCOPE
        );
    }

    public function mergeConfig(array $values): void
    {
        // the config returned from getReportConfig is readonly
        // so we create a new writable one here
        $config = \Pimcore\Config::getReportConfig();
        $config = array_merge($config, $values);

        $this->write($config);
    }
}
