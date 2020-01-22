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

namespace Pimcore\DataObject\Import\ColumnConfig\Operator;

class PHPCode extends AbstractOperator
{
    /**
     * @var \stdClass
     */
    protected $config;

    /**
     * @var string
     */
    protected $phpClass;

    /**
     * @var string
     */
    protected $additionalData;

    /**
     * @var OperatorInterface
     */
    private $instance;

    /**
     * PHPCode constructor.
     *
     * @param \stdClass $config
     * @param mixed|null $context
     */
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->config = $config;
        $this->phpClass = (string)$config->phpClass;
        $this->additionalData = (string) $config->additionalData;
    }

    /**
     * @return string
     */
    public function getPhpClass(): string
    {
        return $this->phpClass;
    }

    /**
     * @param string $phpClass
     */
    public function setPhpClass(string $phpClass)
    {
        $this->phpClass = $phpClass;
        $this->instance = null;
    }

    /**
     * @return string
     */
    public function getAdditionalData(): string
    {
        return $this->additionalData;
    }

    /**
     * @param string $additionalData
     */
    public function setAdditionalData(string $additionalData): void
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @param \Pimcore\Model\Element\ElementInterface $element
     * @param mixed $target
     * @param array $rowData
     * @param int $colIndex
     * @param array $context
     *
     * @throws \Exception
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        if (null === $this->instance) {
            $this->instance = $this->buildInstance();
        }

        $this->instance->process($element, $target, $rowData, $colIndex, $context);
    }

    /**
     * @return OperatorInterface
     *
     * @throws \Exception
     */
    private function buildInstance(): OperatorInterface
    {
        $phpClass = $this->getPhpClass();

        if ($phpClass && class_exists($phpClass)) {
            $operatorInstance = new $phpClass($this->config, $this->context);

            return $operatorInstance;
        } else {
            throw new \Exception('PHPCode operator class does not exist: ' . $phpClass);
        }
    }
}
