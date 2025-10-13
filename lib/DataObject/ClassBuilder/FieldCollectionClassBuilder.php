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

        $cd .= ClassDefinition\Service::buildFieldConstantsCode(...$definition->getFieldDefinitions());

        $cd .= 'protected string $type = "' . $definition->getKey() . "\";\n";

        foreach ($definition->getFieldDefinitions() as $key => $def) {
            $cd .= 'protected $' . $key . ";\n";
        }

        $cd .= "\n\n";

        $fdDefs = $definition->getFieldDefinitions();
        foreach ($fdDefs as $def) {
            $cd .= $def->getGetterCodeFieldcollection($definition);

            if ($def instanceof ClassDefinition\Data\Localizedfields) {
                $cd .= $def->getGetterCode($definition);
            }

            $cd .= $def->getSetterCodeFieldcollection($definition);

            if ($def instanceof ClassDefinition\Data\Localizedfields) {
                $cd .= $def->getSetterCode($definition);
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        return $cd;
    }
}
