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

use Pimcore\DataObject\GridColumnConfig\ConfigElementInterface;

abstract class AbstractOperator implements OperatorInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @var ConfigElementInterface[]
     */
    protected $childs;

    public function __construct(\stdClass $config, $context = null)
    {
        $this->label = $config->label;
        $this->childs = $config->childs;
        $this->context = $context;
    }

    /**
     * @return ConfigElementInterface[]
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * @return bool
     */
    public function expandLocales()
    {
        return false;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }
}
