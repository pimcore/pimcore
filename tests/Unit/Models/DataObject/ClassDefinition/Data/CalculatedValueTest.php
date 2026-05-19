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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\Data\CalculatedValue as CalculatedValueData;
use Pimcore\Tests\Support\Test\TestCase;

class CalculatedValueTest extends TestCase
{
    public function testCalculatorDataDefaultsToNullOnFieldDefinition(): void
    {
        $fd = new CalculatedValueDefinition();

        $this->assertNull($fd->getCalculatorData());
    }

    public function testCalculatorDataCanBeSetAndReadOnFieldDefinition(): void
    {
        $fd = new CalculatedValueDefinition();
        $fd->setCalculatorData('{"factor":2}');

        $this->assertSame('{"factor":2}', $fd->getCalculatorData());
    }

    public function testCalculatorDataCanBeResetToNullOnFieldDefinition(): void
    {
        $fd = new CalculatedValueDefinition();
        $fd->setCalculatorData('something');
        $fd->setCalculatorData(null);

        $this->assertNull($fd->getCalculatorData());
    }

    public function testCalculatorDataDefaultsToNullOnRuntimeData(): void
    {
        $data = new CalculatedValueData('myField');

        $this->assertNull($data->getCalculatorData());
    }

    public function testCalculatorDataCanBeSetAndReadOnRuntimeData(): void
    {
        $data = new CalculatedValueData('myField');
        $data->setCalculatorData('arg-value');

        $this->assertSame('arg-value', $data->getCalculatorData());
    }
}
