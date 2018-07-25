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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Value;

abstract class AbstractValue implements ValueInterface
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
     * @var mixed
     */
    protected $context;

    public function __construct($config, $context = null)
    {
        $this->attribute = $config->attribute;
        $this->label     = $config->label;
        $this->context   = $context;
    }

    public function getLabel()
    {
        return $this->label;
    }
}
