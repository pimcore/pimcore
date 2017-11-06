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

use Pimcore\Targeting\DataProvider\Session;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class Event implements DataProviderDependentConditionInterface
{
    /**
     * @var string|null
     */
    private $key;

    /**
     * @var mixed|null
     */
    private $value;

    /**
     * @param null|string $key
     * @param null|string $value
     */
    public function __construct(string $key = null, $value = null)
    {
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public static function fromConfig(array $config)
    {
        $key = $config['key'] ?? null;
        if (!empty($key)) {
            $key = (string)$key;
        }

        return new static(
            $key,
            $config['value'] ?? null
        );
    }

    /**
     * @inheritDoc
     */
    public function getDataProviderKeys(): array
    {
        return [Session::PROVIDER_KEY];
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return !empty($this->key);
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $bag = $visitorInfo->get(Session::PROVIDER_KEY);
        if (!($bag && $bag instanceof NamespacedAttributeBag)) {
            return false;
        }

        $events = $bag->get('events', []);

        foreach ($events as $event) {
            if ($event['key'] === $this->key) {
                if (null === $this->value || $event['value'] === $this->value) {
                    return true;
                }
            }
        }

        return false;
    }
}
