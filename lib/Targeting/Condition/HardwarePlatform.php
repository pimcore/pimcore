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

use Pimcore\Targeting\DataProvider\Device;
use Pimcore\Targeting\DataProviderDependentInterface;
use Pimcore\Targeting\Model\VisitorInfo;

class HardwarePlatform extends AbstractVariableCondition implements DataProviderDependentInterface
{
    /**
     * @var null|string
     */
    private $platform;

    /**
     * Mapping from admin UI values to DeviceDetector results. If value
     * is an array, in_array is used to determine match.
     *
     * @var array
     */
    protected static $deviceMapping = [
        'smartphone' => 'mobile',
        'phablet' => 'mobile',
        'feature phone' => 'mobile',
    ];

    public function __construct(string $platform = null)
    {
        $this->platform = $platform;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['platform'] ?? null);
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
        return !empty($this->platform);
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $device = $visitorInfo->get(Device::PROVIDER_KEY);

        if (!$device || empty($device) || true === ($device['is_bot'] ?? false)) {
            return false;
        }

        $deviceInfo = $device['device'] ?? null;
        if (!$deviceInfo) {
            return false;
        }

        $platform = $deviceInfo['type'] ?? null;
        if (!empty($platform) && isset(static::$deviceMapping[$platform])) {
            $platform = static::$deviceMapping[$platform];
        }

        if ($this->matchesPlatform($platform)) {
            $this->setMatchedVariable('platform', $platform);

            return true;
        }

        return false;
    }

    private function matchesPlatform(string $platform = null): bool
    {
        if (empty($platform)) {
            return false;
        }

        if ('all' === $this->platform) {
            return true;
        }

        return $platform === $this->platform;
    }
}
