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

class PHPObjectBrickContainerClassDumper implements PHPObjectBrickContainerClassDumperInterface
{
    public function __construct(
        protected ObjectBrickContainerClassBuilderInterface $classBuilder,
        protected Filesystem $filesystem
    ) {
    }

    public function dumpContainerClasses(Definition $definition): void
    {
        $objectClassesFolders = array_filter(array_unique(array_map('realpath', [
            PIMCORE_CLASS_DEFINITION_DIRECTORY,
            PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
        ])));
        $containerDefinition = [];

        foreach ($definition->getClassDefinitions() as $cl) {
            $containerDefinition[$cl['classname']][$cl['fieldname']][] = $definition->getKey();
        }

        $list = new Definition\Listing();
        $list = $list->load();
        foreach ($list as $def) {
            if ($definition->getKey() !== $def->getKey()) {
                $classDefinitions = $def->getClassDefinitions();
                if (!empty($classDefinitions)) {
                    foreach ($classDefinitions as $cl) {
                        $containerDefinition[$cl['classname']][$cl['fieldname']][] = $def->getKey();
                    }
                }
            }
        }

        $includedFiles = [];

        foreach ($containerDefinition as $classId => $cd) {
            foreach ($objectClassesFolders as $objectClassesFolder) {
                $file = $objectClassesFolder . '/definition_' . $classId . '.php';
                if (!file_exists($file)) {
                    continue;
                }

                $realFile = realpath($file);

                if (isset($includedFiles[$realFile])) {
                    continue;
                }

                $includedFiles[$realFile] = true;
                $class = include $file;

                if (!$class) {
                    continue;
                }

                foreach ($cd as $fieldname => $brickKeys) {
                    $containerClass = $this->classBuilder->buildContainerClass($definition, $class, $fieldname, $brickKeys);
                    $folder = $definition->getContainerClassFolder($class->getName());

                    if (!is_dir($folder)) {
                        $this->filesystem->mkdir($folder, 0775);
                    }

                    $file = $folder . '/' . ucfirst($fieldname) . '.php';
                    $this->filesystem->dumpFile($file, $containerClass);
                }
            }
        }
    }
}
