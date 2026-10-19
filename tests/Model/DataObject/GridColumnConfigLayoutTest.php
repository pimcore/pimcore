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

    public function tearDown(): void
    {
        $this->user?->delete();
        $this->customLayout?->delete();
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

        $workspace = new User\Workspace\DataObject();
        $workspace->setCid(1);
        $workspace->setCpath('/');
        $workspace->setList(true);
        $workspace->setView(true);
        $workspace->setLayouts($mainClass->getId() . '_' . $this->customLayout->getId());

        $this->user = new User();
        $this->user->setParentId(0);
        $this->user->setName('grid-column-config-' . uniqid());
        $this->user->setActive(true);
        $this->user->setAdmin(false);
        $this->user->setPermissions(['objects']);
        $this->user->setWorkspacesObject([$workspace]);
        $this->user->save();

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
     * @return string[]
     */
    private function collectFieldNames(ClassDefinition\Data|ClassDefinition\Layout|null $node): array
    {
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
