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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\DefinitionModifier;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionClass;
use ReflectionMethod;

class DefinitionModifierTest extends ModelTestCase
{
    const _CLASS = 'class';

    const _FIELDCOLLECTION = 'fieldcollection';

    const _OBJECTBRICK = 'objectbrick';

    const LOOP_COUNT = 2;

    const PANEL_NAME_PREFIX = 'panel';

    const DATA_NAME_PREFIX = 'input';

    private function getDataToAdd(string $dataName = (self::DATA_NAME_PREFIX . '1')): ClassDefinition\Data\Input
    {
        $input = new ClassDefinition\Data\Input();
        $input->setName($dataName);

        return $input;
    }

    private function getDatasToAdd(): array
    {
        $datas = [];
        for ($i = 0; $i < self::LOOP_COUNT; $i++) {
            $datas[] = $this->getDataToAdd(self::PANEL_NAME_PREFIX . $i);
        }

        return $datas;
    }

    private function getPanelToAdd(string $panelName = (self::PANEL_NAME_PREFIX . '1')): ClassDefinition\Layout\Panel
    {
        $panel = new ClassDefinition\Layout\Panel();
        $panel->setName($panelName);

        return $panel;
    }

    private function getPanelsToAdd(): array
    {
        $panels = [];
        for ($i = 0; $i < 2; $i++) {
            $panels[] = $this->getPanelToAdd(self::PANEL_NAME_PREFIX . $i);
        }

        return $panels;
    }

    private function getNameOfExistingPanel(string $type): string
    {
        if ($type === self::_CLASS) {
            return 'MyLayout';
        } elseif ($type === self::_FIELDCOLLECTION) {
            return 'Layout';
        } else {
            return 'Layout';
        }
    }

    private function getNameOfExistingData(string $type): string
    {
        if ($type === self::_CLASS) {
            return 'date';
        } elseif ($type === self::_FIELDCOLLECTION) {
            return 'fieldinput1';
        } else {
            return 'brickinput';
        }
    }

    private function getNameOfNonExistant(): string
    {
        return 'doestNotExist';
    }

    private function getLayoutDefinitionOfClass(): ClassDefinition\Layout
    {
        $object = ClassDefinition::getByName('unittest');
        /** @var ClassDefinition\Layout\Panel $panel */
        $panel = $object->getLayoutDefinitions();

        return $panel;
    }

    private function getLayoutDefinitionOfFieldcollection(): ClassDefinition\Layout
    {
        $fieldcollection = \Pimcore\Model\DataObject\Fieldcollection\Definition::getByKey('unittestfieldcollection');
        /** @var ClassDefinition\Layout\Panel $panel */
        $panel = $fieldcollection->getLayoutDefinitions();

        return $panel;
    }

    private function getLayoutDefinitionOfObjectbrick(): ClassDefinition\Layout
    {
        $objectbrick = \Pimcore\Model\DataObject\Objectbrick\Definition::getByKey('unittestBrick');
        /** @var ClassDefinition\Layout\Panel $panel */
        $panel = $objectbrick->getLayoutDefinitions();

        return $panel;
    }

    private function getDefinitionByType(string $type, bool $collectGarbage = true): ClassDefinition\Layout
    {
        if ($collectGarbage) {
            Pimcore::collectGarbage();
        }

        if ($type === self::_CLASS) {
            return $this->getLayoutDefinitionOfClass();
        } elseif ($type === self::_FIELDCOLLECTION) {
            return $this->getLayoutDefinitionOfFieldcollection();
        } else {
            return $this->getLayoutDefinitionOfObjectbrick();
        }
    }

    private function doForEachType(string $function, callable $assert, bool $isInsert = false, array $types = [self::_CLASS, self::_FIELDCOLLECTION, self::_OBJECTBRICK]): void
    {
        $definitionAppender = new DefinitionModifier();

        foreach ($types as $type) {
            // #### panel to data ####
            $panelIndex = $this->findElement($this->getDefinitionByType($type), $this->getNameOfExistingPanel($type));
            $this->assertTrue($panelIndex >= 0);
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingPanel($type), $this->getDataToAdd());
            $this->assertTrue($result, 'panel to data at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingPanel($type), [$this->getDataToAdd()], $panelIndex);
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingPanel($type), $this->getDatasToAdd());
            $this->assertTrue($result, 'panel to datas at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingPanel($type), $this->getDatasToAdd(), $panelIndex);

            // #### panel to panel ####
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingPanel($type), $this->getPanelToAdd());
            $this->assertTrue($result, 'panel to panel at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingPanel($type), [$this->getPanelToAdd()], $panelIndex);
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingPanel($type), $this->getPanelsToAdd());
            $this->assertTrue($result, 'panel to panels at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingPanel($type), $this->getPanelsToAdd(), $panelIndex);

            // #### data to data ####
            $dataIndex = $this->findElement($this->getDefinitionByType($type), $this->getNameOfExistingData($type));
            $this->assertTrue($dataIndex >= 0);
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingData($type), $this->getDataToAdd());
            $this->assertTrue($result === !$isInsert, 'data to data at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingData($type), [$this->getDataToAdd()], $dataIndex);
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingData($type), $this->getDatasToAdd());
            $this->assertTrue($result === !$isInsert, 'data to datas at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingData($type), $this->getDatasToAdd(), $dataIndex);

            // #### data to panel ####
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingData($type), $this->getPanelToAdd());
            $this->assertTrue($result === !$isInsert, 'data to panel at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingData($type), [$this->getPanelToAdd()], $dataIndex);
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfExistingData($type), $this->getPanelsToAdd());
            $this->assertTrue($result === !$isInsert, 'data to panels at \'' . $type . '\'');
            $assert($this->getDefinitionByType($type, false), $this->getNameOfExistingData($type), $this->getPanelsToAdd(), $dataIndex);

            // #### Add on non Existing
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfNonExistant(), $this->getPanelToAdd());
            $this->assertFalse($result, 'non-existant to panel at \'' . $type . '\'');
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfNonExistant(), $this->getPanelsToAdd());
            $this->assertFalse($result, 'non-existant to panels at \'' . $type . '\'');
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfNonExistant(), $this->getDataToAdd());
            $this->assertFalse($result, 'non-existant to data at \'' . $type . '\'');
            $result = $definitionAppender->$function($this->getDefinitionByType($type), $this->getNameOfNonExistant(), $this->getDatasToAdd());
            $this->assertFalse($result, 'non-existant to datas at \'' . $type . '\'');
        }
    }

    private function findElement(ClassDefinition\Layout $layoutDefinition, string $name, bool $returnObject = false): mixed
    {
        $children = $layoutDefinition->getChildren();
        $found = false;
        $index = -1;
        /**
         * try to find field
         *
         * @var ClassDefinition\Layout $child
         */
        foreach ($children as $index => $child) {
            if ($child->getName() == $name) {
                $found = true;

                break;
            }
        }

        if ($found) {
            return $returnObject ? $child : $index;
        } else {
            //if not found, call recursive
            foreach ($children as $index => $child) {
                if ($child instanceof ClassDefinition\Layout) {
                    $return = $this->findElement($child, $name, $returnObject);
                    if ((!$returnObject && $return >= 0) || ($returnObject && $return !== null)) {
                        return $return;
                    }
                }
            }

            return $returnObject ? null : -1;
        }
    }

    public function testAppendingFields(): void
    {
        $callable = function () {
            $args = func_get_args();
            $layoutDefinition = $args[0];
            $name = $args[1];
            $fields = $args[2];
            $oldIndex = $args[3];

            $this->assertTrue($this->findElement($layoutDefinition, $name) === $oldIndex,
                'appending assertion - old element still exists at oldIndex');
            /** @var ClassDefinition\Layout\Panel|ClassDefinition\Data\Input $field */
            $idx = $oldIndex;
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                $elementIndex = $this->findElement($layoutDefinition, $fieldName);
                $this->assertTrue($elementIndex > $idx, 'appending assertion - new elements are inserted in order with first element after given');
                $idx = $elementIndex;
            }
        };

        $this->doForEachType('appendFields', $callable);
    }

    public function testPrependingFields(): void
    {
        $callable = function () {
            $args = func_get_args();
            $layoutDefinition = $args[0];
            $name = $args[1];
            $fields = $args[2];
            $oldIndex = $args[3];

            $this->assertTrue($this->findElement($layoutDefinition, $name) === ($oldIndex + count($fields)),
                'prepending assertion - old element still exists at count(addedFields) + oldIndex');
            /** @var ClassDefinition\Layout\Panel|ClassDefinition\Data\Input $field */
            $idx = -1;
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                $elementIndex = $this->findElement($layoutDefinition, $fieldName);
                $this->assertTrue($elementIndex > $idx, 'prepending assertion - new elements are inserted in order');
                $idx = $elementIndex;
            }
            /** @var ClassDefinition\Layout\Panel|ClassDefinition\Data\Input $lastElement */
            $lastElement = $fields[count($fields) - 1];
            $this->assertTrue($this->findElement($layoutDefinition, $name) > $this->findElement($layoutDefinition, $lastElement->getName()),
                'prepending assertion - last element is located before the given element');
        };

        $this->doForEachType('prependFields', $callable);
    }

    public function testReplacingField(): void
    {
        $callable = function () {
            $args = func_get_args();
            $layoutDefinition = $args[0];
            $name = $args[1];
            $fields = $args[2];
            $oldIndex = $args[3];

            $this->assertFalse($this->findElement($layoutDefinition, $name) >= 0, 'replace assertion - old element removed');
            $cnt = 0;
            /** @var ClassDefinition\Layout\Panel|ClassDefinition\Data\Input $field */
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                $this->assertTrue($this->findElement($layoutDefinition, $fieldName) === ($oldIndex + $cnt), 'replace assertion - new element exists at pos of old');
                $cnt++;
            }
        };

        $this->doForEachType('replaceField', $callable);
    }

    public function testRemovingField(): void
    {
        $callable = function () {
            $args = func_get_args();
            $layoutDefinition = $args[0];
            $name = $args[1];

            $this->assertFalse($this->findElement($layoutDefinition, $name) >= 0, 'remove assertion');
        };

        $this->doForEachType('removeField', $callable);
    }

    public function testInsertingFieldsFront(): void
    {
        $callable = function () {
            $args = func_get_args();
            $layoutDefinition = $args[0];
            $name = $args[1];
            $fields = $args[2];
            $oldIndex = $args[3];

            $this->assertTrue($this->findElement($layoutDefinition, $name) === $oldIndex,
                'insertingFront assertion - old element still exists at same index');
            $parent = $this->findElement($layoutDefinition, $name, true);

            if ($parent instanceof ClassDefinition\Layout) {
                $cnt = 0;
                /** @var ClassDefinition\Layout\Panel|ClassDefinition\Data\Input $field */
                foreach ($fields as $field) {
                    $fieldName = $field->getName();
                    $this->assertTrue($this->findElement($parent, $fieldName) === $cnt, 'intertingFront assertion - new elements are at front');
                    $cnt++;
                }
            }
        };

        $this->doForEachType('insertFieldsFront', $callable, true);
    }

    public function testInsertingFieldsBack(): void
    {
        $callable = function () {
            $args = func_get_args();
            $layoutDefinition = $args[0];
            $name = $args[1];
            $fields = $args[2];
            $oldIndex = $args[3];

            $this->assertTrue($this->findElement($layoutDefinition, $name) === $oldIndex,
                'insertingFront assertion - old element still exists at same index');
            $parent = $this->findElement($layoutDefinition, $name, true);

            if ($parent instanceof ClassDefinition\Layout) {
                $cnt = 0;
                /** @var ClassDefinition\Layout\Panel|ClassDefinition\Data\Input $field */
                foreach ($fields as $field) {
                    $fieldName = $field->getName();
                    $this->assertTrue($this->findElement($parent, $fieldName) === (count($parent->getChildren()) - count($fields)) + $cnt,
                        'intertingFront assertion - new elements are at back' . count($parent->getChildren()));
                    $cnt++;
                }
            }
        };

        $this->doForEachType('insertFieldsBack', $callable, true);
    }

    public function testDeleteDeletedDataComponentsInLayoutDefinitionWithOneField(): void
    {
        $classDef = new ClassDefinition();

        $delElement = new ClassDefinition\Data\Input();
        $delElement->setName('delete1');
        $classDef->setDeletedDataComponents([$delElement]);
        $layoutDef = new ClassDefinition\Layout\Panel();

        $panel1 = new ClassDefinition\Layout\Panel();
        $panel1->setChildren([$delElement]);

        $layoutDef->setChildren([$panel1]);

        $delMethod = self::getMethod($classDef, 'deleteDeletedDataComponentsInLayoutDefinition');
        $delMethod->invokeArgs($classDef, [$layoutDef, $layoutDef]);

        $this->assertEmpty($layoutDef->getChildren()[0]->getChildren());
    }

    public function testDeleteDeletedDataComponentsInLayoutDefinitionWithMoreFields(): void
    {
        $classDef = new ClassDefinition();

        $delElements = [];
        for ($i = 0; $i < 5; $i++) {
            $delElement = new ClassDefinition\Data\Input();
            $delElement->setName('delete' . $i);
            array_push($delElements, $delElement);
        }

        $classDef->setDeletedDataComponents($delElements);

        $keepElements = [];
        for ($i = 0; $i < 3; $i++) {
            $keepElement = new ClassDefinition\Data\Input();
            $keepElement->setName('keep' . $i);
            array_push($keepElements, $keepElement);
        }

        $layoutDef = new ClassDefinition\Layout\Panel();

        $panel1 = new ClassDefinition\Layout\Panel();
        $panel1->setChildren([$delElements[0], $delElements[1], $delElements[2], $keepElements[0], $delElements[3], $delElements[4], $keepElements[1], $keepElements[2], ]);

        $layoutDef->setChildren([$panel1]);

        $delMethod = self::getMethod($classDef, 'deleteDeletedDataComponentsInLayoutDefinition');
        $delMethod->invokeArgs($classDef, [$layoutDef, $layoutDef]);

        $this->assertTrue($layoutDef->getChildren()[0]->getChildren()[0] === $keepElements[0]);
        $this->assertTrue($layoutDef->getChildren()[0]->getChildren()[1] === $keepElements[1]);
        $this->assertTrue($layoutDef->getChildren()[0]->getChildren()[2] === $keepElements[2]);
    }

    private static function getMethod(object|string $class, string $name): ReflectionMethod
    {
        $class = new ReflectionClass($class);

        return $class->getMethod($name);
    }

    public function testGetByIdIgnoreCaseWithoutValidId(): void
    {
        $id = '-9999';
        $checkVal = ClassDefinition::getByIdIgnoreCase($id);

        $this->assertNull($checkVal);
    }

    public function testGetByIdIgnoreCaseWithValidId(): void
    {
        $id = 'Inheritance';
        $checkVal = ClassDefinition::getByIdIgnoreCase($id);

        $this->assertNotEmpty($checkVal);
    }
}
