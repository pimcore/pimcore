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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig;

use Pimcore\DataObject\GridColumnConfig\Operator\Factory\OperatorFactoryInterface;
use Pimcore\DataObject\GridColumnConfig\Operator\OperatorInterface;
use Pimcore\DataObject\GridColumnConfig\Value\Factory\ValueFactoryInterface;
use Pimcore\DataObject\GridColumnConfig\Value\ValueInterface;
use Psr\Container\ContainerInterface;

class Service
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
     * @param mixed|null $context
     *
     * @return array
     */
    public function buildOutputDataConfig(array $jsonConfigs, $context = null): array
    {
        $config = $this->doBuildConfig($jsonConfigs, [], $context);

        return $config;
    }

    /**
     * @param \stdClass[] $jsonConfigs
     * @param array $config
     * @param mixed|null $context
     *
     * @return ConfigElementInterface[]
     */
    private function doBuildConfig(array $jsonConfigs, array $config, $context = null): array
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

                $config[] = $this->buildOperator($configElement->class, $configElement, $context);
            }
        }

        return $config;
    }

    private function buildOperator(string $name, \stdClass $configElement, $context = null): OperatorInterface
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
