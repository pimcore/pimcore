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

class Browser extends AbstractVariableCondition implements DataProviderDependentInterface
{
    /**
     * @var null|string
     */
    private $browser;

    public function __construct(string $browser = null)
    {
        $this->browser = $browser;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        return new static($config['browser'] ?? null);
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
        return !empty($this->browser);
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

        $client = $device['client'] ?? null;
        if (!$client) {
            return false;
        }

        $type = $client['type'] ?? null;
        $name = $client['name'] ?? null;

        if ($this->browser === 'ie') {
            $this->browser = 'Internet Explorer';
        }

        if ('browser' === $type && strtolower($name ?? '') === strtolower($this->browser)) {
            $this->setMatchedVariables([
                'type' => $type,
                'name' => $name,
            ]);

            return true;
        }

        return false;
    }
}
