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

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class PHPCode extends AbstractOperator
{
    private \stdClass $config;

    private string $phpClass;

    private ?OperatorInterface $instance = null;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->config = $config;
        $this->phpClass = $config->phpClass ?? '';
    }

    public function getPhpClass(): string
    {
        return $this->phpClass;
    }

    public function setPhpClass(string $phpClass): void
    {
        $this->phpClass = $phpClass;
        $this->instance = null;
    }

    public function getLabel(): string
    {
        return $this->getInstance()->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        try {
            return $this->getInstance()->getLabeledValue($element);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    private function getInstance(): OperatorInterface
    {
        if (null === $this->instance) {
            $this->instance = $this->buildInstance();
        }

        return $this->instance;
    }

    /**
     * @throws \Exception
     */
    private function buildInstance(): OperatorInterface
    {
        $phpClass = $this->getPhpClass();

        if ($phpClass && class_exists($phpClass)) {
            $operatorInstance = new $phpClass($this->config, $this->context);

            return $operatorInstance;
        }

        throw new \Exception('PHPCode operator class does not exist: ' . $phpClass);
    }
}
