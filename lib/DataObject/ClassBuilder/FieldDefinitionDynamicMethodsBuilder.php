<?php

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

namespace Pimcore\DataObject\ClassBuilder;

use Pimcore\Model\DataObject\ClassDefinition;

class FieldDefinitionDynamicMethodsBuilder implements FieldDefinitionDynamicMethodsBuilderInterface
{
    public function buildFieldDefinition(ClassDefinition $classDefinition, ClassDefinition\Data $fieldDefinition): string
    {
        $text = '';

        if ($fieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
            $text .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'|$fieldDefinition getBy'.ucfirst(
                    $fieldDefinition->getName()
                ).'($field, $value, $locale = null, $limit = 0, $offset = 0, $objectTypes = null)'."\n";

            foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDefinition) {
                $text .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                        $classDefinition->getName()
                    ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                        $classDefinition->getName()
                    ).'|null getBy'.ucfirst(
                        $localizedFieldDefinition->getName()
                    ).'($value, $locale = null, $limit = 0, $offset = 0, $objectTypes = null)'."\n";
            }
        } elseif ($fieldDefinition->isFilterable()) {
            $text .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'|null getBy'.ucfirst($fieldDefinition->getName()).'($value, $limit = 0, $offset = 0, $objectTypes = null)'."\n";
        }

        return $text;
    }
}
