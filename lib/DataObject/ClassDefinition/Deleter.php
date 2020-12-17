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

class Deleter implements DeleterInterface
{
    protected $classDirectory;
    protected $definitionPathResolver;

    public function __construct(
        string $classDirectory,
        DefinitionPathResolverInterface $definitionPathResolver
    )
    {
        $this->classDirectory = $classDirectory;
        $this->definitionPathResolver = $definitionPathResolver;
    }

    public function deleteClassDefinition(ClassDefinition $classDefinition)
    {
        $definitionFile = $this->definitionPathResolver->resolvePath($classDefinition->getName());

        @unlink($this->classDirectory.ucfirst($classDefinition->getName()).'.php');
        @unlink($this->classDirectory.ucfirst($classDefinition->getName()).'/Listing.php');
        @rmdir($this->classDirectory.ucfirst($classDefinition->getName()));
        @unlink($definitionFile);
    }
}
