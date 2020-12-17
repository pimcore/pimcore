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

use Pimcore\Model\DataObject\ClassDefinition;

class PHPFileDocBlockGenerator implements PHPFileDocBlockGeneratorInterface
{
    protected $phpFieldDocBlockGenerator;

    public function __construct(PHPFileFieldDocBlockGeneratorInterface $phpFieldDocBlockGenerator)
    {
        $this->phpFieldDocBlockGenerator = $phpFieldDocBlockGenerator;
    }

    public function generateDocBlock(ClassDefinition $classDefinition)
    {
        $cd = '';

        $cd .= '/** ';
        $cd .= "\n";
        $cd .= '* Inheritance: '.($classDefinition->getAllowInherit() ? 'yes' : 'no')."\n";
        $cd .= '* Variants: '.($classDefinition->getAllowVariants() ? 'yes' : 'no')."\n";

        if ($classDefinition->getDescription()) {
            $description = str_replace(['/**', '*/', '//'], '', $classDefinition->getDescription());
            $description = str_replace("\n", "\n* ", $description);

            $cd .= '* '.$description."\n";
        }

        $cd .= "\n\n";
        $cd .= "Fields Summary: \n";

        $cd = $this->getInfoDocBlockForFields($classDefinition, $cd, 1);

        $cd .= '*/ ';

        return $cd;
    }

    protected function getInfoDocBlockForFields(ClassDefinition $definition, $text, $level)
    {
        foreach ($definition->getFieldDefinitions() as $fd) {
            $text .= $this->phpFieldDocBlockGenerator->generateFieldDocBlock($definition, $fd, $level);

            if (method_exists($fd, 'getFieldDefinitions')) {
                $text = $this->getInfoDocBlockForFields($fd, $text, $level + 1);
            }
        }

        return $text;
    }
}
