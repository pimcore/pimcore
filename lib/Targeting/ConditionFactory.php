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

namespace Pimcore\Targeting;

use Pimcore\Event\Targeting\BuildConditionEvent;
use Pimcore\Event\TargetingEvents;
use Pimcore\Targeting\Condition\ConditionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConditionFactory implements ConditionFactoryInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $conditions = [];

    /**
     * @var array
     */
    private $blacklistedKeys = ['type', 'operator', 'bracketLeft', 'bracketRight'];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        array $conditions
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->conditions = $conditions;
    }

    /**
     * @inheritDoc
     */
    public function build(array $config): ConditionInterface
    {
        /** @var string $type */
        $type = $config['type'] ?? null;

        if (empty($type)) {
            throw new \InvalidArgumentException('Invalid condition: Type is not set');
        }

        if (!isset($this->conditions[$type])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid condition: Condition with type "%s" is not registered',
                $type
            ));
        }

        $typeConfig = array_filter($config, function ($v, $k) {
            return !in_array($k, $this->blacklistedKeys);
        }, ARRAY_FILTER_USE_BOTH);

        $event = new BuildConditionEvent($type, $this->conditions[$type], $typeConfig);
        $this->eventDispatcher->dispatch(TargetingEvents::BUILD_CONDITION, $event);

        if ($event->hasCondition()) {
            return $event->getCondition();
        }

        return $this->buildInstance($type, $typeConfig);
    }

    protected function buildInstance(string $type, array $config): ConditionInterface
    {
        /** @var ConditionInterface $class */
        $class = $this->conditions[$type];

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf(
                'Configured condition class "%s" for type "%s" does not exist',
                $class,
                $type
            ));
        }

        return $class::fromConfig($config);
    }
}
