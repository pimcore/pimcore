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

namespace Pimcore\DataObject\ClassBuilder;

use Pimcore\Model\DataObject\ClassDefinition;

class FieldDefinitionPropertiesBuilder implements FieldDefinitionPropertiesBuilderInterface
{
    public function buildProperties(ClassDefinition $classDefinition): string
    {
        $cd = '';

        $cd .= 'protected $classId = "' . $classDefinition->getId(). "\";\n";
        $cd .= 'protected $className = "'.$classDefinition->getName().'"'.";\n";

        foreach ($classDefinition->getFieldDefinitions() as $key => $def) {
            if (!$def instanceof ClassDefinition\Data\ReverseObjectRelation && !$def instanceof ClassDefinition\Data\CalculatedValue
            ) {
                $cd .= 'protected $'.$key.";\n";
            }
        }

        return $cd;
    }
}
