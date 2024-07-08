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

use Exception;
use Pimcore;
use Pimcore\Model\DataObject\QuantityValue\Unit;

class InputQuantityValue extends AbstractQuantityValue
{
    protected string|null $value = null;

    public function __construct(?string $value = null, Unit|string $unit = null)
    {
        $this->value = $value;
        parent::__construct($unit);
    }

    public function setValue(string|null $value): void
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    public function getValue(): string|null
    {
        return $this->value;
    }

    /**
     * @throws Exception
     */
    public function __toString(): string
    {
        $value = $this->getValue();
        if ($this->getUnit() instanceof Unit) {
            $translator = Pimcore::getContainer()->get('translator');
            $value .= ' ' . $translator->trans($this->getUnit()->getAbbreviation(), [], 'admin');
        }

        return $value ?? '';
    }
}
