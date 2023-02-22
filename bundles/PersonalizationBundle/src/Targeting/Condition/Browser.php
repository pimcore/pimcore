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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\Device;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProviderDependentInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

class Browser extends AbstractVariableCondition implements DataProviderDependentInterface
{
    private ?string $browser = null;

    public function __construct(string $browser = null)
    {
        $this->browser = $browser;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(array $config): static
    {
        return new static($config['browser'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProviderKeys(): array
    {
        return [Device::PROVIDER_KEY];
    }

    /**
     * {@inheritdoc}
     */
    public function canMatch(): bool
    {
        return !empty($this->browser);
    }

    /**
     * {@inheritdoc}
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $device = $visitorInfo->get(Device::PROVIDER_KEY);

        if (!$device || true === ($device['is_bot'] ?? false)) {
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
