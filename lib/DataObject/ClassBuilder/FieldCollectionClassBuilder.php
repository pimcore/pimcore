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
use Pimcore\Model\DataObject\Fieldcollection\Definition;

class FieldCollectionClassBuilder implements FieldCollectionClassBuilderInterface
{
    public function __construct(protected FieldDefinitionDocBlockBuilderInterface $fieldDefinitionDocBlockBuilder)
    {
    }

    public function buildClass(Definition $definition): string
    {
        $extendClass = 'DataObject\\Fieldcollection\\Data\\AbstractData';
        if ($definition->getParentClass()) {
            $extendClass = $definition->getParentClass();
            $extendClass = '\\' . ltrim($extendClass, '\\');
        }

        $infoDocBlock = '/**' . "\n";
        $infoDocBlock .= " * Fields Summary:\n";

        foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
            $infoDocBlock .= ' * ' . str_replace("\n", "\n * ", trim($this->fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $infoDocBlock .= ' */';

        // create class file
        $cd = '<?php';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject\\Fieldcollection\\Data;';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\\Model\\DataObject;';
        $cd .= "\n";
        $cd .= 'use Pimcore\Model\DataObject\PreGetValueHookInterface;';
        $cd .= "\n\n";

        $implementsParts = [];

        $implements = ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $definition->getImplementsInterfaces());

        $cd .= 'class ' . ucfirst($definition->getKey()) . ' extends ' . $extendClass . $implements . "\n";
        $cd .= '{' . "\n";

        $cd .= 'protected $type = "' . $definition->getKey() . "\";\n";

        if (is_array($definition->getFieldDefinitions()) && count($definition->getFieldDefinitions())) {
            foreach ($definition->getFieldDefinitions() as $key => $def) {
                $cd .= 'protected $' . $key . ";\n";
            }
        }

        $cd .= "\n\n";

        $fdDefs = $definition->getFieldDefinitions();
        if (is_array($fdDefs) && count($fdDefs)) {
            foreach ($fdDefs as $key => $def) {
                $cd .= $def->getGetterCodeFieldcollection($definition);

                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getGetterCode($definition);
                }

                $cd .= $def->getSetterCodeFieldcollection($definition);

                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getSetterCode($definition);
                }
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        return $cd;
    }
}
