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

namespace Pimcore\Bundle\CoreBundle\Command;

use InvalidArgumentException;
use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\ClassDefinitionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classes:changes',
    description: 'outputs the classes that have changes in their definition files'
)]
class ClassesDefinitionsChangesCommand extends AbstractCommand
{
    public function __construct(
        protected ClassDefinitionManager $classDefinitionManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changes = false;
        $cacheStatus = Cache::isEnabled();
        Cache::disable();
        $linesToRead = 100;

        $objectClassesFolders = $this->getObjectClassesFolders();

        foreach ($objectClassesFolders as $folder) {
            foreach (glob($folder . '/*.php') as $file) {
                $name = $this->extractClassName($file, $linesToRead);
                if (!$name) {
                    $output->writeln("Name not found in $file");

                    continue;
                }

                $class = DataObject\ClassDefinition::getByName($name);
                if ($this->classDefinitionManager->hasChanges($class)) {
                    $changes = true;
                    $output->writeln($name);
                }
            }
        }

        if ($cacheStatus) {
            Cache::enable();
        }

        if (!$changes) {
            $output->writeln('No changes found');
        }

        return 0;
    }

    private function getObjectClassesFolders(): array
    {
        return array_filter(array_unique(array_map('realpath', [
            PIMCORE_CLASS_DEFINITION_DIRECTORY,
            PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
        ])));
    }

    private function extractClassName(string $file, int $linesToRead): ?string
    {
        $realFile = realpath($file);
        $src = $this->readLines($realFile, $linesToRead);

        if (
            preg_match(
                "/\\\\ClassDefinition::__set_state\s*\(\s*array\s*\(.*?'name'\s*=>\s*'([^']*)'/su",
                $src,
                $matches
            )
        ) {
            return $matches[1];
        }

        // when the name could not be found within the lines to read, we try to extract it from the filename
        $filenameWithoutExt = pathinfo($realFile, PATHINFO_FILENAME);
        if (preg_match('/^definition_(.*)$/', $filenameWithoutExt, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function readLines(string $filename, int $linesToRead): string
    {
        $lines = [];

        if (!file_exists($filename)) {
            throw new InvalidArgumentException("File does not exist: $filename");
        }

        $handle = fopen($filename, 'r');
        if ($handle) {
            $count = 0;
            while (($line = fgets($handle)) !== false && $count < $linesToRead) {
                $lines[] = $line;
                $count++;
            }
            fclose($handle);
        }

        return implode('', $lines);
    }
}
