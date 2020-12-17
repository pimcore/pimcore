<?php


declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\ClassDefinition;

use Pimcore\File;
use Pimcore\Model\DataObject\ClassDefinition;

class PHPFileGenerator implements PHPFileGeneratorInterface
{
    protected $classDirectory;
    protected $docBlockGenerator;
    protected $definitionFileGenerator;

    public function __construct(
        string $classDirectory,
        PHPFileDocBlockGeneratorInterface $docBlockGenerator,
        DefinitionFileGeneratorInterface $definitionFileGenerator
    )
    {
        $this->classDirectory = $classDirectory;
        $this->docBlockGenerator = $docBlockGenerator;
        $this->definitionFileGenerator = $definitionFileGenerator;
    }

    public function generatePHPFile(ClassDefinition $classDefinition)
    {
        $infoDocBlock = $this->docBlockGenerator->generateDocBlock($classDefinition);

        // create class for object
        $extendClass = 'Concrete';
        if ($classDefinition->getParentClass()) {
            $extendClass = $classDefinition->getParentClass();
            $extendClass = '\\'.ltrim($extendClass, '\\');
        }

        // create directory if not exists
        if (!is_dir($this->classDirectory)) {
            File::mkdir($this->classDirectory);
        }

        $fielDefinitions = $classDefinition->getFieldDefinitions();

        $cd = '<?php ';
        $cd .= "\n\n";
        $cd .= $infoDocBlock;
        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;';
        $cd .= "\n";
        $cd .= 'use Pimcore\Model\DataObject\PreGetValueHookInterface;';
        $cd .= "\n\n";
        $cd .= "/**\n";
        $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst($classDefinition->getName()).'\Listing getList()'."\n";

        if (is_array($fielDefinitions)) {
            foreach ($fielDefinitions as $key => $def) {
                if ($def instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                            $classDefinition->getName()
                        ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                            $classDefinition->getName()
                        ).' getBy'.ucfirst(
                            $def->getName()
                        ).' ($field, $value, $locale = null, $limit = 0, $offset = 0) '."\n";

                    foreach ($def->getFieldDefinitions() as $localizedFieldDefinition) {
                        $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                                $classDefinition->getName()
                            ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                                $classDefinition->getName()
                            ).' getBy'.ucfirst(
                                $localizedFieldDefinition->getName()
                            ).' ($value, $locale = null, $limit = 0, $offset = 0) '."\n";
                    }
                } elseif ($def->isFilterable()) {
                    $cd .= '* @method static \\Pimcore\\Model\\DataObject\\'.ucfirst(
                            $classDefinition->getName()
                        ).'\Listing|\\Pimcore\\Model\\DataObject\\'.ucfirst(
                            $classDefinition->getName()
                        ).' getBy'.ucfirst($def->getName()).' ($value, $limit = 0, $offset = 0) '."\n";
                }
            }
        }
        $cd .= "*/\n\n";

        $implementsParts = [];

        $implements = ClassDefinition\Service::buildImplementsInterfacesCode($implementsParts, $classDefinition->getImplementsInterfaces());

        $cd .= 'class '.ucfirst($classDefinition->getName()).' extends '.$extendClass. $implements . ' {';
        $cd .= "\n\n";

        $useParts = [];

        $cd .= ClassDefinition\Service::buildUseTraitsCode($useParts, $classDefinition->getUseTraits());

        $cd .= 'protected $o_classId = "' . $classDefinition->getId(). "\";\n";
        $cd .= 'protected $o_className = "'.$classDefinition->getName().'"'.";\n";

        if (is_array($fielDefinitions)) {
            foreach ($fielDefinitions as $key => $def) {
                if (!$def instanceof DataObject\ClassDefinition\Data\ReverseManyToManyObjectRelation && !$def instanceof DataObject\ClassDefinition\Data\CalculatedValue
                ) {
                    $cd .= 'protected $'.$key.";\n";
                }
            }
        }

        $cd .= "\n\n";

        $cd .= '/**'."\n";
        $cd .= '* @param array $values'."\n";
        $cd .= '* @return \\Pimcore\\Model\\DataObject\\'.ucfirst($classDefinition->getName())."\n";
        $cd .= '*/'."\n";
        $cd .= 'public static function create($values = array()) {';
        $cd .= "\n";
        $cd .= "\t".'$object = new static();'."\n";
        $cd .= "\t".'$object->setValues($values);'."\n";
        $cd .= "\t".'return $object;'."\n";
        $cd .= '}';

        $cd .= "\n\n";

        if (is_array($fielDefinitions)) {
            foreach ($fielDefinitions as $key => $def) {
                if ($def instanceof DataObject\ClassDefinition\Data\ReverseManyToManyObjectRelation) {
                    continue;
                }

                // get setter and getter code
                $cd .= $def->getGetterCode($classDefinition);
                $cd .= $def->getSetterCode($classDefinition);

                // call the method "classSaved" if exists, this is used to create additional data tables or whatever which depends on the field definition, for example for localizedfields
                if (method_exists($def, 'classSaved')) {
                    $def->classSaved($classDefinition);
                }
            }
        }

        $cd .= "}\n";
        $cd .= "\n";

        $classFile = $this->classDirectory.ucfirst($classDefinition->getName()).'.php';
        if (!is_writable(dirname($classFile)) || (is_file($classFile) && !is_writable($classFile))) {
            throw new \Exception('Cannot write class file in '.$classFile.' please check the rights on this directory');
        }
        File::put($classFile, $cd);

        // create class for object list
        $extendListingClass = 'DataObject\\Listing\\Concrete';
        if ($classDefinition->getListingParentClass()) {
            $extendListingClass = $classDefinition->getListingParentClass();
            $extendListingClass = '\\'.ltrim($extendListingClass, '\\');
        }

        // create list class
        $cd = '<?php ';

        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject\\'.ucfirst($classDefinition->getName()).';';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= "/**\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())." current()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] load()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] getData()\n";
        $cd .= ' */';
        $cd .= "\n\n";
        $cd .= 'class Listing extends '.$extendListingClass.' {';
        $cd .= "\n\n";

        $cd .= ClassDefinition\Service::buildUseTraitsCode([], $classDefinition->getListingUseTraits());

        $cd .= 'protected $classId = "'. $classDefinition->getId()."\";\n";
        $cd .= 'protected $className = "'.$classDefinition->getName().'"'.";\n";

        $cd .= "\n\n";

        if (\is_array($fielDefinitions)) {
            foreach ($fielDefinitions as $key => $def) {
                if ($def instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    foreach ($def->getFieldDefinitions() as $localizedFieldDefinition) {
                        $cd .= $localizedFieldDefinition->getFilterCode();
                    }
                } elseif ($def->isFilterable()) {
                    $cd .= $def->getFilterCode();
                }
            }
        }

        $cd .= "\n\n";
        $cd .= "}\n";

        File::mkdir($this->classDirectory.ucfirst($classDefinition->getName()));

        $classListFile = $this->classDirectory.ucfirst($classDefinition->getName()).'/Listing.php';
        if (!is_writable(dirname($classListFile)) || (is_file($classListFile) && !is_writable($classListFile))) {
            throw new \Exception(
                'Cannot write class file in '.$classListFile.' please check the rights on this directory'
            );
        }
        File::put($classListFile, $cd);
    }
}
