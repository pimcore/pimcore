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

namespace Pimcore\Tests\Model\Inheritance;

use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\Objectbrick\Data\UnittestBrick;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * When a mandatory field is empty, validation retries with the parent's data in inherited-values mode.
 * The mode must be restored when that retry fails, not only when it succeeds.
 *
 * @group model.inheritance.validation
 */
class ValidationTest extends ModelTestCase
{
    private bool $inheritedValuesBackup = false;

    private bool $dirtyDetectionBackup = false;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();

        $this->inheritedValuesBackup = DataObject::doGetInheritedValues();
        $this->dirtyDetectionBackup = DataObject::isDirtyDetectionDisabled();
    }

    public function tearDown(): void
    {
        DataObject::setGetInheritedValues($this->inheritedValuesBackup);
        // a failed add of a new object leaves dirty detection disabled as well
        DataObject::setDisableDirtyDetection($this->dirtyDetectionBackup);
        parent::tearDown();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function inheritedValuesProvider(): array
    {
        return [
            'inherited values off' => [false],
            'inherited values on' => [true],
        ];
    }

    /**
     * @dataProvider inheritedValuesProvider
     */
    public function testFailedRetryRestoresInheritedValuesForObjectField(bool $inheritedValues): void
    {
        $child = $this->createChildOfEmptyParent();

        $this->assertInheritedValuesRestoredAfterFailedSave(
            $child,
            $child->getClass()->getFieldDefinition('normalinput'),
            $inheritedValues
        );
    }

    /**
     * @dataProvider inheritedValuesProvider
     */
    public function testFailedRetryRestoresInheritedValuesForLocalizedField(bool $inheritedValues): void
    {
        $child = $this->createChildOfEmptyParent();
        $localizedFields = $child->getClass()->getFieldDefinition('localizedfields');
        $this->assertInstanceOf(Data\Localizedfields::class, $localizedFields);

        $this->assertInheritedValuesRestoredAfterFailedSave(
            $child,
            $localizedFields->getFieldDefinition('input'),
            $inheritedValues
        );
    }

    /**
     * @dataProvider inheritedValuesProvider
     */
    public function testFailedRetryRestoresInheritedValuesForObjectbrickField(bool $inheritedValues): void
    {
        $child = $this->createChildOfEmptyParent(true);
        $child->getMybricks()->setUnittestBrick(new UnittestBrick($child));

        $this->assertInheritedValuesRestoredAfterFailedSave(
            $child,
            Definition::getByKey('unittestBrick')->getFieldDefinition('brickinput'),
            $inheritedValues
        );
    }

    private function createChildOfEmptyParent(bool $withBrick = false): Inheritance
    {
        // unpublished, so the parent itself skips the mandatory check
        $parent = new Inheritance();
        $parent->setKey('parent');
        $parent->setParentId(1);
        $parent->setPublished(false);
        if ($withBrick) {
            $parent->getMybricks()->setUnittestBrick(new UnittestBrick($parent));
        }
        $parent->save();

        $child = new Inheritance();
        $child->setKey('child');
        $child->setParentId($parent->getId());
        $child->setPublished(true);

        return $child;
    }

    private function assertInheritedValuesRestoredAfterFailedSave(
        Inheritance $child,
        ?Data $fieldDefinition,
        bool $inheritedValues
    ): void {
        $this->assertNotNull($fieldDefinition);
        $mandatory = $fieldDefinition->getMandatory();
        $fieldDefinition->setMandatory(true);
        DataObject::setGetInheritedValues($inheritedValues);

        try {
            $child->save();
            $this->fail('Expected a ValidationException for the empty mandatory field');
        } catch (ValidationException) {
            $this->assertSame($inheritedValues, DataObject::doGetInheritedValues());
        } finally {
            $fieldDefinition->setMandatory($mandatory);
        }
    }
}
