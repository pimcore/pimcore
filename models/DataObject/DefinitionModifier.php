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

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

final class DefinitionModifier
{
    /**
     * appends valid $fieldsToAdd to a $layoutDefinition element with $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToAdd
     *
     */
    public function appendFields(Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToAdd): bool
    {
        $callable = function () use ($fieldsToAdd) {
            return $this->add($fieldsToAdd, true, func_get_args());
        };

        return $this->findField($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * prepends valid $fieldsToAdd to a $layoutDefinition element with $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToAdd
     *
     */
    public function prependFields(Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToAdd): bool
    {
        $callable = function () use ($fieldsToAdd) {
            return $this->add($fieldsToAdd, false, func_get_args());
        };

        return $this->findField($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * inserts valid $fieldsToAdd into a $layoutDefinition element of type Layout that is given by $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToInsert
     *
     */
    public function insertFieldsFront(Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToInsert): bool
    {
        $callable = function () use ($fieldsToInsert) {
            return $this->insert($fieldsToInsert, false, func_get_args());
        };

        return $this->findField($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * inserts valid $fieldsToAdd into a $layoutDefinition element of type Layout that is given by $nameToFind
     *
     * @param Data|Data[]|Layout|Layout[] $fieldsToInsert
     *
     */
    public function insertFieldsBack(Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldsToInsert): bool
    {
        $callable = function () use ($fieldsToInsert) {
            return $this->insert($fieldsToInsert, true, func_get_args());
        };

        return $this->findField($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * replaces a $layoutDefinition element, that is specified by $nameToFind, with $field
     *
     * @param Data|Data[]|Layout|Layout[] $fieldReplacements
     *
     */
    public function replaceField(Layout $layoutDefinition, string $nameToFind, array|Data|Layout $fieldReplacements): bool
    {
        $callable = function () use ($fieldReplacements) {
            return $this->replace($fieldReplacements, func_get_args());
        };

        return $this->findField($layoutDefinition, $nameToFind, $callable);
    }

    public function removeField(Layout $layoutDefinition, string $nameToFind): bool
    {
        $callable = function () {
            return $this->remove(func_get_args());
        };

        return $this->findField($layoutDefinition, $nameToFind, $callable);
    }

    /**
     * looks for a name in a $layoutDefinition. This may be a Panel or Data Attribute. If there is such a name a
     * callback is executed - passing the parent, its child which was found by name and the child-index it was found at
     * to edit upon.
     *
     *
     */
    public function findField(Data\Localizedfields|Layout $layoutDefinition, string $nameToFind, callable $callback): bool
    {
        $found = false;
        $index = null;
        $children = $layoutDefinition->getChildren();
        $child = null;

        /**
         * try to find field
         *
         * @var Layout $child
         */
        foreach ($children as $index => $child) {
            if ($child->getName() == $nameToFind) {
                $found = true;

                break;
            }
        }

        if ($found) {
            return $callback($layoutDefinition, $child, $index);
        } else {
            //if not found, call recursive
            foreach ($children as $index => $child) {
                if ($child instanceof Layout || $child instanceof Data\Localizedfields) {
                    if ($this->findField($child, $nameToFind, $callback)) {
                        return true;
                    }
                }
            }

            return false;
        }
    }

    /**
     * appends/prepends $fieldsToAdd to a $layoutDefinition element at a given index
     *
     * @param bool $append if set the element gets appended. Otherwise it will be prepended
     * @param Data|Data[]|Layout|Layout[] $fieldsToAdd
     */
    private function add(array|Data|Layout $fieldsToAdd, bool $append, array $args): bool
    {
        $fieldsToAdd = is_array($fieldsToAdd) ? $fieldsToAdd : [$fieldsToAdd];
        $layoutDefinition = $args[0];
        $children = $layoutDefinition->getChildren();
        $insertIndex = $append ? $args[2] + 1 : $args[2];
        array_splice($children, $insertIndex, 0, $fieldsToAdd);
        $layoutDefinition->setChildren($children);

        return true;
    }

    /**
     * inserts valid $fieldsToAdd into a $layoutDefinition element at a given index
     *
     * @param bool $append if set the element gets appended. Otherwise it will be prepended
     * @param Data|Data[]|Layout|Layout[] $fieldsToInsert
     */
    private function insert(array|Data|Layout $fieldsToInsert, bool $append, array $args): bool
    {
        $fieldsToInsert = is_array($fieldsToInsert) ? $fieldsToInsert : [$fieldsToInsert];
        $child = $args[1];
        if ($child instanceof Data) {
            return false;
        }

        $nodeChildren = $child->getChildren();
        $insertIndex = $append ? count($nodeChildren) : 0;
        array_splice($nodeChildren, $insertIndex, 0, $fieldsToInsert);
        $child->setChildren($nodeChildren);

        return true;
    }

    /**
     * replaces a $layoutDefinition element at a given index with valid $fieldsToAdd
     *
     * @param Data|Data[]|Layout|Layout[] $fieldReplacements
     */
    private function replace(array|Data|Layout $fieldReplacements, array $args): bool
    {
        $fieldReplacements = is_array($fieldReplacements) ? $fieldReplacements : [$fieldReplacements];
        $layoutDefinition = $args[0];
        $children = $layoutDefinition->getChildren();
        array_splice($children, $args[2], 1, $fieldReplacements);
        $layoutDefinition->setChildren($children);

        return true;
    }

    /**
     * removes a given field
     */
    private function remove(array $args): bool
    {
        $layoutDefinition = $args[0];
        $children = $layoutDefinition->getChildren();
        array_splice($children, $args[2], 1);
        $layoutDefinition->setChildren($children);

        return true;
    }
}
