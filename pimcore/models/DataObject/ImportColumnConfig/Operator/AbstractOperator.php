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

namespace Pimcore\Model\DataObject\ImportColumnConfig\Operator;


use Pimcore\Model\DataObject\ImportColumnConfig\AbstractConfigElement;

abstract class AbstractOperator extends AbstractConfigElement
{
    /**
     * @var ConfigElementInterface
     */
    protected $childs;

    public function __construct($config, $context = null)
    {
        $this->childs = $config->childs;
        $this->context = $context;
    }

    /**
     * @return ConfigElementInterface
     */
    public function getChilds()
    {
        return $this->childs;
    }


    /**
     * @return null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param null $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

}
