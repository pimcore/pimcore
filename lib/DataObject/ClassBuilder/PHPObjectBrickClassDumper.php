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

use Pimcore\Model\DataObject\Objectbrick\Definition;
use Symfony\Component\Filesystem\Filesystem;

class PHPObjectBrickClassDumper implements PHPObjectBrickClassDumperInterface
{
    public function __construct(
        protected ObjectBrickClassBuilderInterface $classBuilder,
        protected Filesystem $filesystem
    ) {
    }

    public function dumpPHPClasses(Definition $definition): void
    {
        $classFilePath = $definition->getPhpClassFile();
        $phpClass = $this->classBuilder->buildClass($definition);

        $this->filesystem->dumpFile($classFilePath, $phpClass);
    }
}
