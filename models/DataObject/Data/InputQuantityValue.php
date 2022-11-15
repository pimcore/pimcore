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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\QuantityValue\Unit;

class InputQuantityValue extends QuantityValue
{
    /**
     * @param string|null $value
     * @param string|Unit|null $unit
     */
    public function __construct($value = null, Unit|string $unit = null)
    {
        $this->value = $value;
        parent::__construct($unit);
    }

    public function setValue(float|int|null $value)
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    public function getValue(): float|int|null
    {
        return $this->value;
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
            $translator = \Pimcore::getContainer()->get('translator');
            $value .= ' ' . $translator->trans($this->getUnit()->getAbbreviation(), [], 'admin');
        }

        return $value;
    }
}
