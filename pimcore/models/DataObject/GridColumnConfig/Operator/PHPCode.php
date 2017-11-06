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

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

class PHPCode extends AbstractOperator
{
    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->config = $config;
        $this->phpClass = $config->phpClass;
    }

    public function getLabeledValue($element)
    {
        // this is just a placeholder
    }

    /**
     * @return mixed
     */
    public function getPhpClass()
    {
        return $this->phpClass;
    }

    /**
     * @param mixed $phpClass
     */
    public function setPhpClass($phpClass)
    {
        $this->phpClass = $phpClass;
    }

    public function getRealInstance()
    {
        $phpClass = $this->getPhpClass();
        if ($phpClass && class_exists($phpClass)) {
            $operatorInstance = new $phpClass($this->config, $this->context);

            return $operatorInstance;
        }
    }
}
