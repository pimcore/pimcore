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

use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Filesystem\Filesystem;

class PHPClassDumper implements PHPClassDumperInterface
{
    public function __construct(
        protected ClassBuilderInterface $classBuilder,
        protected ListingClassBuilderInterface $listingClassBuilder,
        protected Filesystem $filesystem
    ) {
    }

    public function dumpPHPClasses(ClassDefinition $classDefinition): void
    {
        $classFilePath = $classDefinition->getPhpClassFile();
        $phpClass = $this->classBuilder->buildClass($classDefinition);

        $this->filesystem->dumpFile($classFilePath, $phpClass);
        if (!file_exists($classFilePath)) {
            throw new Exception(sprintf('Cannot write class file in %s please check the rights on this directory', $classFilePath));
        }

        $listingClassFilePath = $classDefinition->getPhpListingClassFile();
        $listingPhpClass = $this->listingClassBuilder->buildListingClass($classDefinition);

        $this->filesystem->dumpFile($listingClassFilePath, $listingPhpClass);
        if (!file_exists($listingClassFilePath)) {
            throw new Exception(
                sprintf('Cannot write class file in %s please check the rights on this directory', $listingClassFilePath)
            );
        }
    }
}
