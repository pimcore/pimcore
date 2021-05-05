<?php

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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\DataObject\GridColumnConfig\ConfigElementInterface;
use Pimcore\Tool;

abstract class AbstractOperator implements OperatorInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var array
     */
    protected array $context = [];

    /**
     * @var ConfigElementInterface[]
     */
    protected $childs;

    /**
     * @param \stdClass $config
     * @param array $context
     */
    public function __construct(\stdClass $config, array $context = [])
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

    /**
     * @return mixed|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string[]
     */
    public function getValidLanguages()
    {
        return Tool::getValidLanguages();
    }
}
