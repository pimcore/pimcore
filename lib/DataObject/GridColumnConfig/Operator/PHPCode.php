<?php
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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

class PHPCode extends AbstractOperator
{
    /**
     * @var \stdClass
     */
    private $config;

    /**
     * @var string
     */
    private $phpClass;

    /**
     * @var OperatorInterface
     */
    private $instance;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->config = $config;
        $this->phpClass = $config->phpClass ?? '';
    }

    public function getPhpClass(): string
    {
        return $this->phpClass;
    }

    public function setPhpClass(string $phpClass)
    {
        $this->phpClass = $phpClass;
        $this->instance = null;
    }

    public function getLabel()
    {
        return $this->getInstance()->getLabel();
    }

    public function getLabeledValue($element)
    {
        try {
            return $this->getInstance()->getLabeledValue($element);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getInstance(): OperatorInterface
    {
        if (null === $this->instance) {
            $this->instance = $this->buildInstance();
        }

        return $this->instance;
    }

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
