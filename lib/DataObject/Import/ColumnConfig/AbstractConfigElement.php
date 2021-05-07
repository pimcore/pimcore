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

namespace Pimcore\DataObject\Import\ColumnConfig;

/**
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
abstract class AbstractConfigElement implements ConfigElementInterface
{
    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var mixed|null
     */
    protected $context;

    public function __construct(\stdClass $config, $context = null)
    {
        $this->attribute = $config->attribute ?? null;
        $this->label = $config->label ?? null;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return mixed|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed|null $context
     */
    public function setContext($context = null)
    {
        $this->context = $context;
    }
}
