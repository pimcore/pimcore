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

use Pimcore\File;
use Pimcore\Model\DataObject\ClassDefinition;

class PHPClassDumper implements PHPClassDumperInterface
{
    public function __construct(
        protected ClassBuilderInterface $classBuilder,
        protected ListingClassBuilderInterface $listingClassBuilder
    ) {
    }

    public function dumpPHPClasses(ClassDefinition $classDefinition): void
    {
        $classFilePath = $classDefinition->getPhpClassFile();
        $phpClass = $this->classBuilder->buildClass($classDefinition);

        if (File::put($classFilePath, $phpClass) === false) {
            throw new \Exception(sprintf('Cannot write class file in %s please check the rights on this directory', $classFilePath));
        }

        $listingClassFilePath = $classDefinition->getPhpListingClassFile();
        $listingPhpClass = $this->listingClassBuilder->buildListingClass($classDefinition);

        if (File::put($listingClassFilePath, $listingPhpClass) === false) {
            throw new \Exception(
                sprintf('Cannot write class file in %s please check the rights on this directory', $listingClassFilePath)
            );
        }
    }
}
