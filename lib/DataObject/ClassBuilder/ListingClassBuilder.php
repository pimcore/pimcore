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

class ListingClassBuilder implements ListingClassBuilderInterface
{
    public function __construct(
        protected ListingClassFieldDefinitionBuilderInterface $fieldDefinitionBuilder
    ) {
    }

    public function buildListingClass(ClassDefinition $classDefinition): string
    {
        // create class for object list
        $extendListingClass = 'DataObject\\Listing\\Concrete';
        if ($classDefinition->getListingParentClass()) {
            $extendListingClass = $classDefinition->getListingParentClass();
            $extendListingClass = '\\'.ltrim($extendListingClass, '\\');
        }

        // create list class
        $cd = '<?php';

        $cd .= "\n\n";
        $cd .= 'namespace Pimcore\\Model\\DataObject\\'.ucfirst($classDefinition->getName()).';';
        $cd .= "\n\n";
        $cd .= 'use Pimcore\\Model;';
        $cd .= "\n";
        $cd .= 'use Pimcore\\Model\\DataObject;';
        $cd .= "\n\n";
        $cd .= "/**\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."|false current()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] load()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] getData()\n";
        $cd .= ' * @method DataObject\\'.ucfirst($classDefinition->getName())."[] getObjects()\n";
        $cd .= ' */';
        $cd .= "\n\n";
        $cd .= 'class Listing extends '.$extendListingClass . "\n";
        $cd .= '{' . "\n";

        $cd .= ClassDefinition\Service::buildUseTraitsCode([], $classDefinition->getListingUseTraits());

        $cd .= 'protected $classId = "'. $classDefinition->getId()."\";\n";
        $cd .= 'protected $className = "'.$classDefinition->getName().'"'.";\n";

        $cd .= "\n\n";

        foreach ($classDefinition->getFieldDefinitions() as $def) {
            $cd .= $this->fieldDefinitionBuilder->buildListingClassFieldDefinition($classDefinition, $def);
        }

        $cd .= "\n\n";
        $cd .= "}\n";

        return $cd;
    }
}
