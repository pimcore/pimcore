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
