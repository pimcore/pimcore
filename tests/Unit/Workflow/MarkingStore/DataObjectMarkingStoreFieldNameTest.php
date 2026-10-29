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

namespace Pimcore\Tests\Unit\Workflow\MarkingStore;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\DataObjectMultipleStateMarkingStore;
use Pimcore\Workflow\MarkingStore\DataObjectSplittedStateMarkingStore;
use Pimcore\Workflow\Place\PlaceConfig;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Workflow\Marking;

/**
 * Regression test for https://github.com/pimcore/pimcore/issues/18634
 *
 * Pimcore generates getters/setters like getProduct_workflow() for a field named "product_workflow",
 * while Symfony's PropertyAccessor looks for getProductWorkflow().
 */
class DataObjectMarkingStoreFieldNameTest extends TestCase
{
    public static function subjectProvider(): array
    {
        return [
            'snake_case' => ['product_workflow', new SnakeCaseSubject()],
            'camelCase' => ['productWorkflow', new CamelCaseSubject()],
            'single word' => ['marking', new SingleWordSubject()],
        ];
    }

    /**
     * @dataProvider subjectProvider
     */
    public function testMultipleStateMarkingStoreReadsAndWritesField(string $fieldName, MarkingSubject $subject): void
    {
        $store = new DataObjectMultipleStateMarkingStore($fieldName);

        $this->assertSame(['draft' => 1], $store->getMarking($subject)->getPlaces());

        $store->setMarking($subject, new Marking(['review' => 1, 'approved' => 1]));

        $this->assertSame(['review', 'approved'], $subject->getStored());
    }

    /**
     * @dataProvider subjectProvider
     */
    public function testSplittedStateMarkingStoreReadsAndWritesField(string $fieldName, MarkingSubject $subject): void
    {
        $subject->defineField($fieldName);

        $workflowManager = $this->createStub(Manager::class);
        $workflowManager->method('getPlaceConfig')->willReturn($this->createStub(PlaceConfig::class));

        $store = new DataObjectSplittedStateMarkingStore(
            'test_wf',
            ['draft', 'review'],
            ['draft' => $fieldName, 'review' => $fieldName],
            PropertyAccess::createPropertyAccessor(),
            $workflowManager
        );

        $this->assertSame(['draft' => 1], $store->getMarking($subject)->getPlaces());

        $store->setMarking($subject, new Marking(['review' => 1]));

        $this->assertSame(['review'], $subject->getStored());
    }

    public function testInjectedPropertyAccessorIsUsedByBothStores(): void
    {
        $subject = new SnakeCaseSubject();
        $accessor = $this->createMock(PropertyAccessorInterface::class);
        $accessor->expects($this->exactly(2))->method('getValue')->with($subject, 'product_workflow')->willReturn(['draft']);
        $accessor->expects($this->once())->method('setValue')->with($subject, 'product_workflow', ['review']);

        $store = new DataObjectMultipleStateMarkingStore('product_workflow', $accessor);
        $store->getMarking($subject);
        $store->setMarking($subject, new Marking(['review' => 1]));

        $subject->defineField('product_workflow');
        $workflowManager = $this->createStub(Manager::class);
        $workflowManager->method('getPlaceConfig')->willReturn($this->createStub(PlaceConfig::class));
        $splitted = new DataObjectSplittedStateMarkingStore('test_wf', ['draft'], ['draft' => 'product_workflow'], $accessor, $workflowManager);
        $splitted->getMarking($subject);

        $this->assertSame(['draft'], $subject->getStored());
    }

    public function testSplittedStateMarkingStoreCastsNumericPlaceNameForSelectField(): void
    {
        $subject = new SelectSubject();
        $workflowManager = $this->createStub(Manager::class);
        $workflowManager->method('getPlaceConfig')->willReturn($this->createStub(PlaceConfig::class));

        $store = new DataObjectSplittedStateMarkingStore(
            'test_wf',
            ['2'],
            ['2' => 'status_field'],
            PropertyAccess::createPropertyAccessor(),
            $workflowManager
        );

        $store->setMarking($subject, new Marking(['2' => 1]));
        $this->assertSame('2', $subject->selected);

        $store->setMarking($subject, new Marking([]));
        $this->assertNull($subject->selected);
    }
}

class SelectSubject extends Concrete
{
    public ?string $selected = null;

    public function __construct()
    {
    }

    public function getClass(): ClassDefinition
    {
        $fd = new Select();
        $fd->setName('status_field');
        $class = new ClassDefinition();
        $class->setFieldDefinitions(['status_field' => $fd]);

        return $class;
    }

    public function getStatus_field(): ?string
    {
        return $this->selected;
    }

    public function setStatus_field(?string $value): static
    {
        $this->selected = $value;

        return $this;
    }
}

/**
 * Stands in for a generated data object class: the stored value and the class definition of the marking field.
 */
abstract class MarkingSubject extends Concrete
{
    protected array $stored = ['draft'];

    private string $definedField = '';

    public function __construct()
    {
    }

    public function defineField(string $fieldName): void
    {
        $this->definedField = $fieldName;
    }

    public function getStored(): array
    {
        return $this->stored;
    }

    public function getClass(): ClassDefinition
    {
        $fd = new Multiselect();
        $fd->setName($this->definedField);

        $class = new ClassDefinition();
        $class->setFieldDefinitions([$this->definedField => $fd]);

        return $class;
    }
}

// accessors as Pimcore generates them: "get" / "set" + ucfirst(field name), underscores are kept
class SnakeCaseSubject extends MarkingSubject
{
    public function getProduct_workflow(): array
    {
        return $this->stored;
    }

    public function setProduct_workflow(array $value): static
    {
        $this->stored = $value;

        return $this;
    }
}

class CamelCaseSubject extends MarkingSubject
{
    public function getProductWorkflow(): array
    {
        return $this->stored;
    }

    public function setProductWorkflow(array $value): static
    {
        $this->stored = $value;

        return $this;
    }
}

class SingleWordSubject extends MarkingSubject
{
    public function getMarking(): array
    {
        return $this->stored;
    }

    public function setMarking(array $value): static
    {
        $this->stored = $value;

        return $this;
    }
}
