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

class DefinitionFileGenerator implements DefinitionFileGeneratorInterface
{
    protected $infoDocBlockGenerator;
    protected $definitionPathResolver;

    public function __construct(
        DefinitionPathResolverInterface $definitionPathResolver,
        PHPFileDocBlockGeneratorInterface $infoDocBlockGenerator
    )
    {
        $this->definitionPathResolver = $definitionPathResolver;
        $this->infoDocBlockGenerator = $infoDocBlockGenerator;
    }

    public function generateDefinitionFile(ClassDefinition $classDefinition)
    {
        $definitionFile = $this->definitionPathResolver->resolvePath($classDefinition->getName());

        if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new \Exception(
                'Cannot write definition file in: '.$definitionFile.' please check write permission on this directory.'
            );
        }

        $clone = clone $classDefinition;
        $clone->setDao(null);
        unset($clone->fieldDefinitions);

        ClassDefinition::cleanupForExport($clone);

        $exportedClass = var_export($clone, true);

        $data = '<?php ';
        $data .= "\n\n";
        $data .= $this->infoDocBlockGenerator->generateDocBlock($classDefinition);
        $data .= "\n\n";

        $data .= "\nreturn ".$exportedClass.";\n";

        \Pimcore\File::putPhpFile($definitionFile, $data);
    }
}
