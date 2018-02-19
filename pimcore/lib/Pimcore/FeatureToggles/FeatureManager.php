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
     * @var FeatureStateInterface[]
     */
    private $states = [];

    /**
     * @var FeatureStateInitializerInterface[]
     */
    private $initializers = [];

    /**
     * @param FeatureContextInterface|null $context
     * @param FeatureStateInitializerInterface[] $initializers
     */
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
        $state = $this->getState($feature::getType());
        if (null === $state) {
            return false;
        }

        return $state->isEnabled($feature, $this->context);
    }

    public function setState(FeatureStateInterface $state)
    {
        $this->states[$state->getType()] = $state;
    }

    public function hasState(string $type): bool
    {
        return null !== $this->getState($type);
    }

    /**
     * @param string $type
     *
     * @return FeatureStateInterface|null
     */
    public function getState(string $type)
    {
        if (isset($this->states[$type])) {
            return $this->states[$type];
        }

        $state = null;
        foreach ($this->getInitializers($type) as $initializer) {
            $state = $initializer->getState($this->context, $state);
        }

        if (null !== $state) {
            $this->states[$type] = $state;
        }

        return $state;
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
        $this->initializers[] = $initializer;
    }

    /**
     * @param string|null $type
     *
     * @return FeatureStateInitializerInterface[]
     */
    public function getInitializers(string $type = null): array
    {
        if (null === $type) {
            return $this->initializers;
        } else {
            return array_values(array_filter($this->initializers, function (FeatureStateInitializerInterface $initializer) use ($type) {
                return $initializer->getType() === $type;
            }));
        }
    }

    /**
     * @param FeatureStateInitializerInterface[] $initializers
     */
    public function setInitializers(array $initializers)
    {
        $this->initializers = [];

        foreach ($initializers as $initializer) {
            $this->addInitializer($initializer);
        }
    }
}
