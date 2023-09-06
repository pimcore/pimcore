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
