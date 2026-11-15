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

use DomainException;
use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\UnittestBrick;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;

/**
 * When a mandatory field is empty, validation retries with the parent's data in inherited-values mode.
 * The mode must be restored when that retry fails, not only when it succeeds.
 *
 * @group model.inheritance.validation
 */
class ValidationTest extends ModelTestCase
{
    private bool $inheritedValuesBackup = false;

    private bool $adminModeBackup = false;

    public function setUp(): void
    {
        // captured first, so tearDown() restores the real state even if the setup below fails
        $this->adminModeBackup = Pimcore::inAdmin();
        $this->inheritedValuesBackup = DataObject::doGetInheritedValues();

        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
    }

    public function tearDown(): void
    {
        DataObject::setGetInheritedValues($this->inheritedValuesBackup);
        if (!$this->adminModeBackup) {
            Pimcore::unsetAdminMode();
        }
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        // class with inheritance and a brick holding localized fields
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_LazyLoadingTest();
        $this->tester->setupFieldcollection_LazyLoadingLocalizedTest();
        $this->tester->setupPimcoreClass_LazyLoading();
        $this->tester->setupObjectbrick_LazyLoadingLocalizedTest();
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

    /**
     * @dataProvider inheritedValuesProvider
     */
    public function testFailedRetryRestoresInheritedValuesForLocalizedFieldInObjectbrick(bool $inheritedValues): void
    {
        $child = $this->createLocalizedBrickChild(null);

        $this->assertInheritedValuesRestoredAfterFailedSave($child, $this->getLocalizedBrickInput(), $inheritedValues);
    }

    /**
     * The retry must read the value of the parent's brick, so the child passes the mandatory check.
     */
    public function testRetryReadsInheritedValueForLocalizedFieldInObjectbrick(): void
    {
        $child = $this->createLocalizedBrickChild('parenttext');
        $fieldDefinition = $this->getLocalizedBrickInput();
        $mandatory = $fieldDefinition->getMandatory();
        $fieldDefinition->setMandatory(true);
        DataObject::setGetInheritedValues(false);

        try {
            $child->save();
        } finally {
            $fieldDefinition->setMandatory($mandatory);
        }

        $this->assertNotNull($child->getId());
        $this->assertFalse(DataObject::doGetInheritedValues());
    }

    /**
     * The classification store has no retry, but it reads each value in inherited-values mode. That mode must be
     * restored when the read throws.
     *
     * @dataProvider inheritedValuesProvider
     */
    public function testFailedClassificationstoreReadRestoresInheritedValues(bool $inheritedValues): void
    {
        $fieldDefinition = (new Inheritance())->getClass()->getFieldDefinition('teststore');
        $this->assertInstanceOf(Data\Classificationstore::class, $fieldDefinition);

        $key = null;
        $group = null;
        $relation = null;

        try {
            $key = new Classificationstore\KeyConfig();
            $key->setStoreId($fieldDefinition->getStoreId());
            $key->setName('validationTestKey');
            $key->setType('input');
            $key->setDefinition(json_encode(new Data\Input()));
            $key->setEnabled(true);
            $key->save();

            $group = new Classificationstore\GroupConfig();
            $group->setStoreId($fieldDefinition->getStoreId());
            $group->setName('validationTestGroup');
            $group->save();

            $newRelation = new Classificationstore\KeyGroupRelation();
            $newRelation->setGroupId($group->getId());
            $newRelation->setKeyId($key->getId());
            $newRelation->save();
            $relation = $newRelation;

            $store = new class([$group->getId() => true]) extends Classificationstore {
                /**
                 * @param array<int, bool> $groups
                 */
                public function __construct(private readonly array $groups)
                {
                    parent::__construct();
                }

                public function getActiveGroups(): array
                {
                    return $this->groups;
                }

                public function getLocalizedKeyValue(
                    int $groupId,
                    int $keyId,
                    ?string $language = 'default',
                    bool $ignoreFallbackLanguage = false,
                    bool $ignoreDefaultLanguage = false
                ): mixed {
                    throw new DomainException('read failed');
                }
            };
            $store->setObject(new Inheritance());

            DataObject::setGetInheritedValues($inheritedValues);
            $fieldDefinition->checkValidity($store);
            $this->fail('Expected the failing read to throw');
        } catch (DomainException $e) {
            $this->assertSame('read failed', $e->getMessage());
            $this->assertSame($inheritedValues, DataObject::doGetInheritedValues());
        } finally {
            // only delete what was actually saved
            $relation?->delete();
            if ($group?->getId()) {
                $group->delete();
            }
            if ($key?->getId()) {
                $key->delete();
            }
        }
    }

    private function createLocalizedBrickChild(?string $parentValue): LazyLoading
    {
        // unpublished, so the parent itself skips the mandatory check
        $parent = new LazyLoading();
        $parent->setKey('parent');
        $parent->setParentId(1);
        $parent->setPublished(false);
        $parentBrick = new LazyLoadingLocalizedTest($parent);
        foreach (Tool::getValidLanguages() as $language) {
            $parentBrick->setLinput($parentValue, $language);
        }
        $parent->getBricks()->setLazyLoadingLocalizedTest($parentBrick);
        $parent->save();

        $child = new LazyLoading();
        $child->setKey('child');
        $child->setParentId($parent->getId());
        $child->setPublished(true);
        $child->getBricks()->setLazyLoadingLocalizedTest(new LazyLoadingLocalizedTest($child));

        return $child;
    }

    private function getLocalizedBrickInput(): Data
    {
        $localizedFields = Definition::getByKey('LazyLoadingLocalizedTest')->getFieldDefinition('localizedfields');
        $this->assertInstanceOf(Data\Localizedfields::class, $localizedFields);
        $fieldDefinition = $localizedFields->getFieldDefinition('linput');
        $this->assertNotNull($fieldDefinition);

        return $fieldDefinition;
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
        Concrete $child,
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
