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

class FieldDefinitionBuilder implements FieldDefinitionBuilderInterface
{
    public function buildFieldDefinition(ClassDefinition $classDefinition, ClassDefinition\Data $fieldDefinition): string
    {
        $cd = $fieldDefinition->getGetterCode($classDefinition);

        if (!$fieldDefinition instanceof ClassDefinition\Data\ReverseObjectRelation) {
            $cd .= $fieldDefinition->getSetterCode($classDefinition);
        }

        // call the method "classSaved" if exists, this is used to create additional data tables or whatever which depends on the field definition, for example for localizedfields
        //TODO Pimcore 11 remove method_exists call
        if (!$fieldDefinition instanceof ClassDefinition\Data\DataContainerAwareInterface && method_exists($fieldDefinition, 'classSaved')) {
            $fieldDefinition->classSaved($classDefinition);
        }

        return $cd;
    }
}
