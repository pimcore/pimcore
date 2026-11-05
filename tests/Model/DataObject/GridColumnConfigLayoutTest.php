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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * @group model.dataobject.service
 */
class GridColumnConfigLayoutTest extends ModelTestCase
{
    private ?CustomLayout $customLayout = null;

    private ?User $user = null;

    private ?ClassDefinition $nestedClass = null;

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_Block();
    }

    public function tearDown(): void
    {
        $this->user?->delete();
        $this->customLayout?->delete();
        $this->nestedClass?->delete();
        parent::tearDown();
    }

    /**
     * The grid column config layout is filtered down to the custom layout the user is restricted to.
     * The filtering must work on a copy: the class definition passed in is usually the cached,
     * shared instance, and every later caller would otherwise only see the filtered layout.
     */
    public function testNonAdminCallDoesNotMutateTheClassLayout(): void
    {
        $mainClass = ClassDefinition::getByName('unittest');
        $this->assertInstanceOf(ClassDefinition::class, $mainClass);

        // A separate instance, so a regression cannot leak into the cached class definition.
        $class = new ClassDefinition();
        $class->setId($mainClass->getId());
        $class->setLayoutDefinitions(Service::cloneDefinition($mainClass->getLayoutDefinitions()));

        $customRoot = (new ClassDefinition\Layout\Panel())->setName('root');
        $customPanel = (new ClassDefinition\Layout\Panel())->setName('Restricted');
        $customPanel->addChild(Service::cloneDefinition($mainClass->getFieldDefinition('firstname')));
        $customRoot->addChild($customPanel);

        $this->customLayout = CustomLayout::create([
            'name' => 'grid-column-config-' . uniqid(),
            'classId' => $mainClass->getId(),
        ]);
        $this->customLayout->setLayoutDefinitions($customRoot);
        $this->customLayout->save();

        $this->user = $this->createNonAdminUser($mainClass, $this->customLayout);

        $fieldNamesBefore = $this->collectFieldNames($class->getLayoutDefinitions());
        $this->assertContains('lastname', $fieldNamesBefore);

        $result = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->user);

        $this->assertSame(
            ['firstname'],
            $this->collectFieldNames($result['layoutDefinition']),
            'the returned layout is filtered down to the custom layout'
        );
        $this->assertSame(
            $fieldNamesBefore,
            $this->collectFieldNames($class->getLayoutDefinitions()),
            'the class definition layout must not be changed by the filtering'
        );
    }

    /**
     * The children of a Block are no top-level fields of the class. Filtering the layout for a non-admin user
     * must keep them, the user sees the same Block as an admin does.
     */
    public function testBlockKeepsItsChildrenForNonAdminWithoutCustomLayout(): void
    {
        $class = ClassDefinition::getByName('unittestBlock');
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $this->user = $this->createNonAdminUser($class);

        $adminResult = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->createAdminUser());
        $result = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->user);

        $expected = $this->collectFieldNames($adminResult['layoutDefinition']);
        $this->assertContains('blockinput', $expected);
        $this->assertSame($expected, $this->collectFieldNames($result['layoutDefinition']));

        $block = $this->findBlock($result['layoutDefinition']);
        $this->assertInstanceOf(ClassDefinition\Data\Block::class, $block);
        $this->assertNotEmpty($block->getFieldDefinitions(), 'the Block in the returned layout has its children');
        $this->assertNotEmpty($result['fieldDefinition']['testblock']->getFieldDefinitions());
    }

    /**
     * Children of a Block the custom layout leaves out are still excluded, everything else is kept.
     */
    public function testBlockChildrenAreFilteredByTheCustomLayout(): void
    {
        $class = ClassDefinition::getByName('unittestBlock');
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $customBlock = Service::cloneDefinition($class->getFieldDefinition('testblock'));
        $customBlock->setChildren([Service::cloneDefinition($class->getFieldDefinition('testblock')->getFieldDefinition('blockinput'))]);
        $customRoot = (new ClassDefinition\Layout\Panel())->setName('root');
        $customRoot->addChild($customBlock);

        $this->customLayout = CustomLayout::create([
            'name' => 'grid-column-config-' . uniqid(),
            'classId' => $class->getId(),
        ]);
        $this->customLayout->setLayoutDefinitions($customRoot);
        $this->customLayout->save();

        $this->user = $this->createNonAdminUser($class, $this->customLayout);

        $result = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->user);

        $this->assertSame(
            ['testblock', 'blockinput'],
            $this->collectFieldNames($result['layoutDefinition'])
        );
        $this->assertSame(['blockinput'], array_keys($result['fieldDefinition']['testblock']->getFieldDefinitions()));
        $this->assertGreaterThan(
            1,
            count($class->getFieldDefinition('testblock')->getFieldDefinitions()),
            'the class definition keeps all children of the Block'
        );
    }

    /**
     * A Block may contain Localizedfields. Their fields are neither top-level fields of the class nor
     * children of the Block's own map, so they are checked against the Localizedfields' definitions.
     */
    public function testLocalizedfieldsInsideBlockKeepTheirChildren(): void
    {
        $class = $this->createBlockWithLocalizedfieldsClass();
        $this->user = $this->createNonAdminUser($class);

        $result = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->user);

        $this->assertSame(
            ['gridblock', 'blockfield', 'localizedfields', 'locfield', 'locother'],
            $this->collectFieldNames($result['layoutDefinition'])
        );
    }

    /**
     * Children of a Localizedfields inside a Block that the custom layout leaves out are excluded as well.
     */
    public function testLocalizedfieldsInsideBlockAreFilteredByTheCustomLayout(): void
    {
        $class = $this->createBlockWithLocalizedfieldsClass();

        $customBlock = Service::cloneDefinition($class->getFieldDefinition('gridblock'));
        $customLocalizedfields = Service::cloneDefinition($customBlock->getFieldDefinition('localizedfields'));
        $customLocalizedfields->setChildren([$customLocalizedfields->getFieldDefinition('locfield')]);
        $customBlock->setChildren([$customBlock->getFieldDefinition('blockfield'), $customLocalizedfields]);
        $customRoot = (new ClassDefinition\Layout\Panel())->setName('root');
        $customRoot->addChild($customBlock);

        $this->customLayout = CustomLayout::create([
            'name' => 'grid-column-config-' . uniqid(),
            'classId' => $class->getId(),
        ]);
        $this->customLayout->setLayoutDefinitions($customRoot);
        $this->customLayout->save();

        $this->user = $this->createNonAdminUser($class, $this->customLayout);

        $result = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->user);

        $this->assertSame(
            ['gridblock', 'blockfield', 'localizedfields', 'locfield'],
            $this->collectFieldNames($result['layoutDefinition'])
        );
        $this->assertSame(
            ['locfield'],
            array_keys($result['fieldDefinition']['gridblock']->getFieldDefinition('localizedfields')->getFieldDefinitions())
        );
    }

    /**
     * Several Localizedfields sections inside a Block are aggregated into one. Fields the custom layout leaves
     * out must not come back through the aggregated sections.
     */
    public function testAggregatedLocalizedfieldsSectionsInsideBlockAreFilteredByTheCustomLayout(): void
    {
        $class = $this->createBlockWithLocalizedfieldsClass(true);

        $customBlock = Service::cloneDefinition($class->getFieldDefinition('gridblock'));
        $customLocalizedfields = Service::cloneDefinition($customBlock->getFieldDefinition('localizedfields'));
        $customLocalizedfields->setReferencedFields([]);
        $customLocalizedfields->setChildren([$customLocalizedfields->getFieldDefinition('locfield')]);
        $customBlock->setChildren([$customBlock->getFieldDefinition('blockfield'), $customLocalizedfields]);
        $customRoot = (new ClassDefinition\Layout\Panel())->setName('root');
        $customRoot->addChild($customBlock);

        $this->customLayout = CustomLayout::create([
            'name' => 'grid-column-config-' . uniqid(),
            'classId' => $class->getId(),
        ]);
        $this->customLayout->setLayoutDefinitions($customRoot);
        $this->customLayout->save();

        $this->user = $this->createNonAdminUser($class, $this->customLayout);

        $result = Service::getCustomLayoutDefinitionForGridColumnConfig($class, 1, $this->user);

        $this->assertContains('locfield', $this->collectFieldNames($result['layoutDefinition']));
        $this->assertNotContains('locother', $this->collectFieldNames($result['layoutDefinition']));
        $this->assertSame(
            ['locfield'],
            array_keys($result['fieldDefinition']['gridblock']->getFieldDefinition('localizedfields')->getFieldDefinitions())
        );
    }

    private function createBlockWithLocalizedfieldsClass(bool $twoSections = false): ClassDefinition
    {
        $localizedfields = new ClassDefinition\Data\Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->addChild((new ClassDefinition\Data\Input())->setName('locfield'));
        if (!$twoSections) {
            $localizedfields->addChild((new ClassDefinition\Data\Input())->setName('locother'));
        }

        $block = new ClassDefinition\Data\Block();
        $block->setName('gridblock');
        $block->addChild((new ClassDefinition\Data\Input())->setName('blockfield'));
        $block->addChild($localizedfields);

        if ($twoSections) {
            $secondSection = new ClassDefinition\Data\Localizedfields();
            $secondSection->setName('localizedfields');
            $secondSection->addChild((new ClassDefinition\Data\Input())->setName('locother'));
            $block->addChild($secondSection);
        }

        $root = (new ClassDefinition\Layout\Panel())->setName('root');
        $root->addChild($block);

        $class = new ClassDefinition();
        $class->setName('unittestGridBlockLocalizedfields');
        $class->setLayoutDefinitions($root);
        $class->save();

        $this->nestedClass = $class;

        return ClassDefinition::getByName('unittestGridBlockLocalizedfields');
    }

    private function createAdminUser(): User
    {
        $admin = new User();
        $admin->setAdmin(true);

        return $admin;
    }

    private function createNonAdminUser(ClassDefinition $class, ?CustomLayout $customLayout = null): User
    {
        $workspace = new User\Workspace\DataObject();
        $workspace->setCid(1);
        $workspace->setCpath('/');
        $workspace->setList(true);
        $workspace->setView(true);
        if ($customLayout) {
            $workspace->setLayouts($class->getId() . '_' . $customLayout->getId());
        }

        $user = new User();
        $user->setParentId(0);
        $user->setName('grid-column-config-' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['objects']);
        $user->setWorkspacesObject([$workspace]);
        $user->save();

        return $user;
    }

    private function findBlock(ClassDefinition\Data|ClassDefinition\Layout|null $node): ?ClassDefinition\Data\Block
    {
        if ($node instanceof ClassDefinition\Data\Block) {
            return $node;
        }

        foreach ($node?->getChildren() ?? [] as $child) {
            if ($block = $this->findBlock($child)) {
                return $block;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function collectFieldNames(ClassDefinition\Data|ClassDefinition\Layout|null $node): array
    {
        if ($node instanceof ClassDefinition\Data\Block || $node instanceof ClassDefinition\Data\Localizedfields) {
            $names = [$node->getName()];
            foreach ($node->getChildren() as $child) {
                array_push($names, ...$this->collectFieldNames($child));
            }

            return $names;
        }

        if ($node instanceof ClassDefinition\Data) {
            return [$node->getName()];
        }

        $names = [];
        foreach ($node?->getChildren() ?? [] as $child) {
            array_push($names, ...$this->collectFieldNames($child));
        }

        return $names;
    }
}
