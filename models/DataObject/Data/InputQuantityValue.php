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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\QuantityValue\Unit;

class InputQuantityValue extends AbstractQuantityValue
{
    /**
     * @var string|null
     */
    protected $value;

    /**
     * @param string|null $value
     * @param Unit|string|null $unit
     */
    public function __construct($value = null, $unit = null)
    {
        $this->value = $value;
        parent::__construct($unit);
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return (string)$this->value;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function __toString()
    {
        $value = $this->getValue();
        if ($this->getUnit() instanceof Unit) {
            $value .= ' ' . $this->getUnit()->getAbbreviation();
        }

        return $value;
    }
}
