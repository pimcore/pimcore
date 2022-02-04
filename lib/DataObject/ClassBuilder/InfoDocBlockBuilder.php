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

class InfoDocBlockBuilder implements InfoDocBlockBuilderInterface
{
    public function __construct(protected FieldDefinitionDocBlockBuilder $fieldDefinitionDocBlockBuilder)
    {
    }

    public function buildInfoDocBlock(ClassDefinition $classDefinition): string
    {
        $cd = '/**' . "\n";
        $cd .= '* Inheritance: '.($classDefinition->getAllowInherit() ? 'yes' : 'no')."\n";
        $cd .= '* Variants: '.($classDefinition->getAllowVariants() ? 'yes' : 'no')."\n";

        if ($classDefinition->getDescription()) {
            $description = str_replace(
                array('/**', '*/', '//', "\n"),
                array('', '', '', "\n* "),
                $classDefinition->getDescription()
            );

            $cd .= '* '.$description."\n";
        }

        $cd .= "\n\n";
        $cd .= "Fields Summary:\n";

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            $cd .= $this->fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition);
        }

        $cd .= '*/';

        return $cd;
    }
}
