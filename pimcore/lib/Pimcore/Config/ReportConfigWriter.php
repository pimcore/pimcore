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

namespace Pimcore\Config;

use Pimcore\Event\Admin\Report\SettingsEvent;
use Pimcore\Event\Admin\ReportEvents;
use Pimcore\File;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles writing/merging report config and emitting an event on config save.
 */
class ReportConfigWriter
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function write(array $settings)
    {
        $settingsEvent = new SettingsEvent($settings);
        $this->eventDispatcher->dispatch(
            ReportEvents::SAVE_SETTINGS,
            $settingsEvent
        );

        $settings = $settingsEvent->getSettings();

        File::putPhpFile(
            $this->getConfigFile(),
            to_php_data_file_format($settings)
        );
    }

    public function mergeConfig(Config $values)
    {
        // the config returned from getReportConfig is readonly
        // so we create a new writable one here
        $config = new Config(
            \Pimcore\Config::getReportConfig()->toArray(),
            true
        );

        $config->merge($values);

        $this->write($config->toArray());
    }

    public function mergeArray(array $values)
    {
        $this->mergeConfig(new Config($values));
    }

    private function getConfigFile(): string
    {
        return \Pimcore\Config::locateConfigFile('reports.php');
    }
}
