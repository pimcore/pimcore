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

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Value;

abstract class AbstractValue implements ValueInterface
{
    protected string $attribute;

    protected string $label;

    protected mixed $context = null;

    public function __construct(\stdClass $config, mixed $context = null)
    {
        $this->attribute = $config->attribute;
        $this->label = $config->label;
        $this->context = $context;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRenderer(): ?string
    {
        return null;
    }
}
