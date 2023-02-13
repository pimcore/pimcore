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

class ClassBuilder implements ClassBuilderInterface
{
    public function __construct(
        protected FieldDefinitionDocBlockBuilderInterface $fieldDefinitionDocBlockBuilder,
        protected FieldDefinitionPropertiesBuilderInterface $propertiesBuilder,
        protected FieldDefinitionBuilderInterface $fieldDefinitionBuilder,
    ) {
    }

    public function buildClass(ClassDefinition $classDefinition): string
    {
        // create class for object
        $extendClass = 'Concrete';
        if ($classDefinition->getParentClass()) {
            $extendClass = $classDefinition->getParentClass();
            $extendClass = '\\'.ltrim($extendClass, '\\');
        }

        $cd = '<?php';
        $cd .= "\n\n";
        $cd .= '/**' . "\n";
        $cd .= ' * Inheritance: '.($classDefinition->getAllowInherit() ? 'yes' : 'no')."\n";
        $cd .= ' * Variants: '.($classDefinition->getAllowVariants() ? 'yes' : 'no')."\n";

        if ($description = $classDefinition->getDescription()) {
            $description = str_replace(
                ['/**', '*/', '//', "\n"],
                ['', '', '', "\n * "],
                $description
            );

            $cd .= ' * '.$description."\n";
        }

        $cd .= " *\n";
        $cd .= " * Fields Summary:\n";

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            $cd .= ' * ' . str_replace("\n", "\n * ", trim($this->fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition))) . "\n";
        }

        $cd .= ' */';
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;';
        $cd .= "\n";
        $cd .= 'use Pimcore\Model\DataObject\PreGetValueHookInterface;';
        $cd .= "\n\n";
        $cd .= "/**\n";
        $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst($classDefinition->getName()).'\Listing getList(array $config = [])'."\n";

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
                $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'|null getBy'.ucfirst(
                    $fieldDefinition->getName()
                ).'(string $field, mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)'."\n";

                foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDefinition) {
                    $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                        $classDefinition->getName()
                    ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                        $classDefinition->getName()
                    ).'|null getBy'.ucfirst(
                        $localizedFieldDefinition->getName()
                    ).'(mixed $value, ?string $locale = null, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)'."\n";
                }
            } elseif ($fieldDefinition->isFilterable()) {
                $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                    $classDefinition->getName()
                ).'|null getBy'.ucfirst($fieldDefinition->getName()).'(mixed $value, ?int $limit = null, int $offset = 0, ?array $objectTypes = null)'."\n";
            }
        }

        $cd .= "*/\n\n";

        $implementsParts = [];

        $implements = ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $classDefinition->getImplementsInterfaces());

        $cd .= 'class '.ucfirst($classDefinition->getName()).' extends '.$extendClass. $implements . "\n";
        $cd .= '{' . "\n";

        $useParts = [];

        $cd .= ClassDefinition\Service::buildUseTraitsCode($useParts, $classDefinition->getUseTraits());
        $cd .= ClassDefinition\Service::buildFieldConstantsCode(...$classDefinition->getFieldDefinitions());

        $cd .= $this->propertiesBuilder->buildProperties($classDefinition);
        $cd .= "\n\n";

        $cd .= '/**'."\n";
        $cd .= '* @param array $values'."\n";
        $cd .= '* @return static'."\n";
        $cd .= '*/'."\n";
        $cd .= 'public static function create(array $values = []): static'."\n";
        $cd .= "{\n";
        $cd .= "\t".'$object = new static();'."\n";
        $cd .= "\t".'$object->setValues($values);'."\n";
        $cd .= "\t".'return $object;'."\n";
        $cd .= '}';

        $cd .= "\n\n";

        foreach ($classDefinition->getFieldDefinitions() as $def) {
            $cd .= $this->fieldDefinitionBuilder->buildFieldDefinition($classDefinition, $def);
        }

        $cd .= "}\n";
        $cd .= "\n";

        return $cd;
    }
}
