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

class PHPFileFieldDocBlockGenerator implements PHPFileFieldDocBlockGeneratorInterface
{
    public function generateFieldDocBlock(ClassDefinition $classDefinition, ClassDefinition\Data $fieldDefinition, int $level)
    {
        return str_pad('', $level, '-').' '.$fieldDefinition->getName().' ['.$fieldDefinition->getFieldtype()."]\n";;
    }
}
