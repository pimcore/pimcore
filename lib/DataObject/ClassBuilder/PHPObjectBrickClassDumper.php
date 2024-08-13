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
