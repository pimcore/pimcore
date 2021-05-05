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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig;

use Pimcore\DataObject\GridColumnConfig\Operator\Factory\OperatorFactoryInterface;
use Pimcore\DataObject\GridColumnConfig\Operator\OperatorInterface;
use Pimcore\DataObject\GridColumnConfig\Value\Factory\ValueFactoryInterface;
use Pimcore\DataObject\GridColumnConfig\Value\ValueInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
final class Service
{
    /**
     * @var ContainerInterface
     */
    private $operatorFactories;

    /**
     * @var ContainerInterface
     */
    private $valueFactories;

    public function __construct(
        ContainerInterface $operatorFactories,
        ContainerInterface $valueFactories
    ) {
        $this->operatorFactories = $operatorFactories;
        $this->valueFactories = $valueFactories;
    }

    /**
     * @param \stdClass[] $jsonConfigs
     * @param array $context
     *
     * @return array
     */
    public function buildOutputDataConfig(array $jsonConfigs, array $context = []): array
    {
        return $this->doBuildConfig($jsonConfigs, [], $context);
    }

    /**
     * @param \stdClass[] $jsonConfigs
     * @param array $config
     * @param array $context
     *
     * @return ConfigElementInterface[]
     */
    private function doBuildConfig(array $jsonConfigs, array $config, array $context = []): array
    {
        if (empty($jsonConfigs)) {
            return $config;
        }

        foreach ($jsonConfigs as $configElement) {
            if ('value' === $configElement->type) {
                $config[] = $this->buildValue($configElement->class, $configElement, $context);
            } elseif ('operator' === $configElement->type) {
                if (!empty($configElement->childs)) {
                    $configElement->childs = $this->doBuildConfig($configElement->childs, [], $context);
                }

                $operator = $this->buildOperator($configElement->class, $configElement, $context);
                if ($operator) {
                    $config[] = $operator;
                }
            }
        }

        return $config;
    }

    /**
     * @param string $name
     * @param \stdClass $configElement
     * @param array $context
     *
     * @return OperatorInterface|null
     */
    private function buildOperator(string $name, \stdClass $configElement, array $context = [])
    {
        if (!$this->operatorFactories->has($name)) {
            throw new \InvalidArgumentException(sprintf('Operator "%s" is not supported', $name));
        }

        /** @var OperatorFactoryInterface $factory */
        $factory = $this->operatorFactories->get($name);

        return $factory->build($configElement, $context);
    }

    private function buildValue(string $name, \stdClass $configElement, $context = null): ValueInterface
    {
        if (!$this->valueFactories->has($name)) {
            throw new \InvalidArgumentException(sprintf('Value "%s" is not supported', $name));
        }

        /** @var ValueFactoryInterface $factory */
        $factory = $this->valueFactories->get($name);

        return $factory->build($configElement, $context);
    }
}
