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
use Pimcore\Model\DataObject\Objectbrick\Definition;

class ObjectBrickContainerClassBuilder implements ObjectBrickContainerClassBuilderInterface
{
    public function buildContainerClass(Definition $definition, ClassDefinition $classDefinition, string $fieldName, array $brickKeys): string
    {
        $className = $definition->getContainerClassName($classDefinition->getName(), $fieldName);
        $namespace = $definition->getContainerNamespace($classDefinition->getName(), $fieldName);

        natcasesort($brickKeys);

        $cd = '<?php';

        $cd .= "\n\n";
        $cd .= 'namespace ' . $namespace . ';';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;';
        $cd .= "\n\n";
        $cd .= 'class ' . $className . ' extends \\Pimcore\\Model\\DataObject\\Objectbrick {';
        $cd .= "\n\n";

        $cd .= 'protected $brickGetters = [' . "'" . implode("','", $brickKeys) . "'];\n";
        $cd .= "\n\n";

        foreach ($brickKeys as $brickKey) {
            $cd .= 'protected \\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickKey) . '|null $' . $brickKey . " = null;\n\n";

            $cd .= '/**' . "\n";
            $cd .= '* @return \\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickKey) . "|null\n";
            $cd .= '*/' . "\n";
            $cd .= 'public function get' . ucfirst($brickKey) . '(bool $includeDeletedBricks = false)' . "\n";
            $cd .= '{' . "\n";

            if ($classDefinition->getAllowInherit()) {
                $cd .= "\t" . 'if(!$this->' . $brickKey . ' && \\Pimcore\\Model\\DataObject::doGetInheritedValues($this->getObject())) { ' . "\n";
                $cd .= "\t\t" . 'try {' . "\n";
                $cd .= "\t\t\t" . '$brickContainer = $this->getObject()->getValueFromParent("' . $fieldName . '");' . "\n";
                $cd .= "\t\t\t" . 'if(!empty($brickContainer)) {' . "\n";
                $cd .= "\t\t\t\t" . '//check if parent object has brick, and if so, create an empty brick to enable inheritance' . "\n";
                $cd .= "\t\t\t\t" . '$parentBrick = $this->getObject()->getValueFromParent("' . $fieldName . '")->get' . ucfirst($brickKey) . '($includeDeletedBricks);'. "\n";
                $cd .= "\t\t\t\t" . 'if (!empty($parentBrick)) {' . "\n";
                $cd .= "\t\t\t\t\t" . '$brickType = "\\\Pimcore\\\Model\\\DataObject\\\Objectbrick\\\Data\\\" . ucfirst($parentBrick->getType());' . "\n";
                $cd .= "\t\t\t\t\t" . '$brick = new $brickType($this->getObject());' . "\n";
                $cd .= "\t\t\t\t\t" . '$brick->setFieldname("' . $fieldName . '");' . "\n";
                $cd .= "\t\t\t\t\t" . '$this->set'. ucfirst($brickKey) . '($brick);' . "\n";
                $cd .= "\t\t\t\t\t" . 'return $brick;' . "\n";
                $cd .= "\t\t\t\t" . '}' . "\n";
                $cd .= "\t\t\t" . "}\n";
                $cd .= "\t\t" . '} catch (InheritanceParentNotFoundException $e) {' . "\n";
                $cd .= "\t\t\t" . '// no data from parent available, continue ...' . "\n";
                $cd .= "\t\t" . '}' . "\n";
                $cd .= "\t" . "}\n";
            }
            $cd .= "\t" . 'if(!$includeDeletedBricks &&' . "\n";
            $cd .= "\t\t" . 'isset($this->' . "$brickKey" . ') &&' . "\n";
            $cd .= "\t\t" . '$this->' . "$brickKey" .'->getDoDelete()) {' . "\n";
            $cd .= "\t\t\t" . 'return null;' . "\n";
            $cd .= "\t" . '}' . "\n";
            $cd .= "\t" . 'return $this->' . $brickKey . ";\n";

            $cd .= "}\n\n";

            $typeDeclaration = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickKey);
            $cd .= '/**' . "\n";
            $cd .= '* @param ' . $typeDeclaration . '|null $' . $brickKey . "\n";
            $cd .= '* @return $this' . "\n";
            $cd .= '*/' . "\n";
            $cd .= 'public function set' . ucfirst($brickKey) . '(?' . $typeDeclaration . ' $' . $brickKey . '): static' . "\n";
            $cd .= '{' . "\n";
            $cd .= "\t" . '$this->' . $brickKey . ' = ' . '$' . $brickKey . ";\n";
            $cd .= "\t" . 'return $this' . ";\n";
            $cd .= "}\n\n";
        }

        $cd .= "}\n";
        $cd .= "\n";

        return $cd;
    }
}
