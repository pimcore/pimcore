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

use Pimcore\Model\DataObject\Fieldcollection\Definition;

class FieldCollectionDocBlockBuilder implements FieldCollectionDocBlockBuilderInterface
{
    public function __construct(protected FieldDefinitionDocBlockBuilder $fieldDefinitionDocBlockBuilder)
    {
    }

    public function buildDocBlock(Definition $definition): string
    {
        $cd = '/**' . "\n";
        $cd .= "Fields Summary:\n";

        foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
            $cd .= $this->fieldDefinitionDocBlockBuilder->buildFieldDefinitionDocBlock($fieldDefinition);
        }

        $cd .= '*/';

        return $cd;
    }
}
