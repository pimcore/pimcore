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
use Pimcore\Model\DataObject\Objectbrick\Definition;

class ObjectBrickClassBuilder implements ObjectBrickClassBuilderInterface
{
    public function __construct(protected FieldDefinitionDocBlockBuilderInterface $fieldDefinitionDocBlockBuilder)
    {
    }

    public function buildClass(Definition $definition): string
    {
        $extendClass = 'DataObject\\Objectbrick\\Data\\AbstractData';
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

        $cd = '<?php';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject\\Objectbrick\\Data;';
        $cd .= "\n\n";

        $useParts = [
            'Pimcore\Model\DataObject',
            'Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException',
            'Pimcore\Model\DataObject\PreGetValueHookInterface',
        ];

        $cd .= ClassDefinition\Service::buildUseCode($useParts);

        $cd .= "\n";

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

        $cd .= '/**' ."\n";
        $cd .= '* ' . ucfirst($definition->getKey()) . ' constructor.' . "\n";
        $cd .= '* @param DataObject\Concrete $object' . "\n";
        $cd .= '*/' . "\n";

        $cd .= 'public function __construct(DataObject\Concrete $object)' . "\n";
        $cd .= '{' . "\n";
        $cd .= "\t" . 'parent::__construct($object);' . "\n";
        $cd .= "\t" .'$this->markFieldDirty("_self");' . "\n";
        $cd .= '}' . "\n";

        $cd .= "\n\n";

        if (is_array($definition->getFieldDefinitions()) && count($definition->getFieldDefinitions())) {
            foreach ($definition->getFieldDefinitions() as $key => $def) {
                $cd .= $def->getGetterCodeObjectbrick($definition);

                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                    $cd .= $def->getGetterCode($definition);
                }

                $cd .= $def->getSetterCodeObjectbrick($definition);

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
