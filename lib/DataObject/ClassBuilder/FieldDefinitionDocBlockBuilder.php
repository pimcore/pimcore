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

class FieldDefinitionDocBlockBuilder implements FieldDefinitionDocBlockBuilderInterface
{
    public function buildFieldDefinitionDocBlock(ClassDefinition\Data $fieldDefinition, int $level = 1): string
    {
        $text = str_pad('', $level, '-').' '.$fieldDefinition->getName().' ['.$fieldDefinition->getFieldtype()."]\n";

        if (method_exists($fieldDefinition, 'getFieldDefinitions')) {
            foreach ($fieldDefinition->getFieldDefinitions() as $subDefinition) {
                $text .= $this->buildFieldDefinitionDocBlock($subDefinition, $level + 1);
            }
        }

        return $text;
    }
}
