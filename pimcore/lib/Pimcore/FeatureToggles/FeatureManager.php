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

namespace Pimcore\FeatureToggles;

class FeatureManager implements FeatureManagerInterface
{
    /**
     * @var FeatureContextInterface
     */
    private $context;

    /**
     * @var array
     */
    private $states = [];

    /**
     * @var array
     */
    private $initializers = [];

    public function __construct(FeatureContextInterface $context = null, array $initializers = [])
    {
        $this->setContext($context ?? new FeatureContext());

        foreach ($initializers as $initializer) {
            $this->addInitializer($initializer);
        }
    }

    public function setContext(FeatureContextInterface $context)
    {
        $this->context = $context;
    }

    public function getContext(): FeatureContextInterface
    {
        return $this->context;
    }

    public function isEnabled(Feature $feature): bool
    {
        $states = $this->getStates($feature::getType());
        if (empty($states)) {
            return false;
        }

        /** @var FeatureStateInterface $state */
        foreach ($states as $state) {
            if ($state->isEnabled($feature, $this->context)) {
                return true;
            }
        }

        return false;
    }

    public function addState(FeatureStateInterface $featureState)
    {
        $type = $featureState->getType();

        if (isset($this->states[$type])) {
            $this->states[$type][] = $featureState;
        } else {
            $this->states[$type] = [$featureState];
        }
    }

    /**
     * @param FeatureStateInterface[] $states
     */
    public function addStates(array $states)
    {
        foreach ($states as $state) {
            $this->addState($state);
        }
    }

    public function setState(FeatureStateInterface $featureState)
    {
        $this->clear($featureState->getType());
        $this->addState($featureState);
    }

    public function hasStates(string $type): bool
    {
        return count($this->getStates($type)) > 0;
    }

    /**
     * @param string $type
     *
     * @return FeatureStateInterface[]
     */
    public function getStates(string $type): array
    {
        if (!isset($this->states[$type]) && isset($this->initializers[$type])) {
            /** @var FeatureStateInitializerInterface $initializer */
            foreach ($this->initializers[$type] as $initializer) {
                $this->addStates($initializer->getStates($this->context));
            }
        }

        return $this->states[$type] ?? [];
    }

    public function getRegisteredTypes(): array
    {
        return array_keys($this->states);
    }

    public function clear(string $type = null)
    {
        if (null !== $type) {
            if (isset($this->states[$type])) {
                unset($this->states[$type]);
            }
        } else {
            $this->states = [];
        }
    }

    public function addInitializer(FeatureStateInitializerInterface $initializer)
    {
        $this->initializers[$initializer->getType()][] = $initializer;
    }

    /**
     * @param string $type
     *
     * @return FeatureStateInitializerInterface[]
     */
    public function getInitializers(string $type): array
    {
        return $this->initializers[$type] ?? [];
    }

    /**
     * @param string $type
     * @param FeatureStateInitializerInterface[] $initializers
     */
    public function setInitializers(string $type, array $initializers)
    {
        $this->initializers[$type] = [];

        foreach ($initializers as $initializer) {
            $this->addInitializer($initializer);
        }
    }

    public function clearInitializers(string $type = null)
    {
        if (null !== $type) {
            if (isset($this->initializers[$type])) {
                unset($this->initializers[$type]);
            }
        } else {
            $this->initializers = [];
        }
    }

    public function getInitializerTypes(): array
    {
        return array_keys($this->initializers);
    }
}
