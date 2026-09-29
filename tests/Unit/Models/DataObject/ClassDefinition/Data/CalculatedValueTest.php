<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\CalculatorClassInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue as CalculatedValueDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue as CalculatedValueData;
use Pimcore\Model\DataObject\Service;
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

    public function testCalculatorDataIsPassedToCalculatorClass(): void
    {
        $data = $this->createContext(
            $this->createClassCalculatorDefinition('{"factor":2}')
        );

        $this->assertSame(
            'computed:{"factor":2}',
            Service::getCalculatedFieldValue(new Concrete(), $data)
        );
    }

    public function testCalculatorDataIsPassedToCalculatorClassInEditMode(): void
    {
        $data = $this->createContext(
            $this->createClassCalculatorDefinition('{"factor":2}')
        );

        $this->assertSame(
            'editmode:{"factor":2}',
            Service::getCalculatedFieldValueForEditMode(new Concrete(), [], $data)
        );
    }

    public function testCalculatorDataIsNullOnContextIfNotConfigured(): void
    {
        $data = $this->createContext(
            $this->createClassCalculatorDefinition(null)
        );

        $this->assertSame(
            'computed:',
            Service::getCalculatedFieldValue(new Concrete(), $data)
        );
    }

    public function testStaleCalculatorDataOnContextIsOverwritten(): void
    {
        $data = $this->createContext(
            $this->createClassCalculatorDefinition(null)
        );
        $data->setCalculatorData('stale');

        $this->assertSame(
            'computed:',
            Service::getCalculatedFieldValue(new Concrete(), $data)
        );
        $this->assertNull($data->getCalculatorData());
    }

    public function testCalculatorDataIsPassedToExpression(): void
    {
        $fd = new CalculatedValueDefinition();
        $fd->setCalculatorType(CalculatedValueDefinition::CALCULATOR_TYPE_EXPRESSION);
        $fd->setCalculatorExpression('data.getCalculatorData()');
        $fd->setCalculatorData('expression-arg');

        $data = $this->createContext($fd);

        $this->assertSame(
            'expression-arg',
            Service::getCalculatedFieldValue(new Concrete(), $data)
        );
    }

    private function createClassCalculatorDefinition(?string $calculatorData): CalculatedValueDefinition
    {
        $fd = new CalculatedValueDefinition();
        $fd->setCalculatorType(CalculatedValueDefinition::CALCULATOR_TYPE_CLASS);
        $fd->setCalculatorClass(CalculatorDataTestCalculator::class);
        $fd->setCalculatorData($calculatorData);

        return $fd;
    }

    private function createContext(CalculatedValueDefinition $fd): CalculatedValueData
    {
        $data = new CalculatedValueData('myField');
        // pass the field definition as key definition so that no class definition lookup is needed
        $data->setContextualData('object', null, null, null, null, null, $fd);

        return $data;
    }
}

class CalculatorDataTestCalculator implements CalculatorClassInterface
{
    public function compute(Concrete $object, CalculatedValueData $context): string
    {
        return 'computed:' . $context->getCalculatorData();
    }

    public function getCalculatedValueForEditMode(Concrete $object, CalculatedValueData $context): string
    {
        return 'editmode:' . $context->getCalculatorData();
    }
}
