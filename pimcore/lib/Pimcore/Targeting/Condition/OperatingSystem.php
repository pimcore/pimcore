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

namespace Pimcore\Targeting\Condition;

use DeviceDetector\DeviceDetector;
use Pimcore\Targeting\DataProvider\Device;
use Pimcore\Targeting\Model\VisitorInfo;

class OperatingSystem implements DataProviderDependentConditionInterface
{
    /**
     * @var null|string
     */
    private $system;

    /**
     * Mapping from admin UI values to DeviceDetector results
     *
     * @var array
     */
    protected static $osMapping = [
        'macos'   => 'MAC',
        'windows' => 'WIN',
        'linux'   => 'LIN',
        'android' => 'AND',
        'ios'     => 'IOS'
    ];

    public function __construct(string $system = null)
    {
        if (!empty($system) && isset(static::$osMapping[$system])) {
            $system = static::$osMapping[$system];
        }

        $this->system = $system;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['system'] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getDataProviderKeys(): array
    {
        return [Device::PROVIDER_KEY];
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return !empty($this->system);
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        /** @var DeviceDetector $dd */
        $dd = $visitorInfo->get(Device::PROVIDER_KEY);

        if (!$dd || $dd->isBot()) {
            return false;
        }

        return $dd->getOs('short_name') === $this->system;
    }
}
