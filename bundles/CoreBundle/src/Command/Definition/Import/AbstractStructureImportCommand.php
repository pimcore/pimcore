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

namespace Pimcore\Bundle\CoreBundle\Command\Definition\Import;

use InvalidArgumentException;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\ModelInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @internal
 */
abstract class AbstractStructureImportCommand extends AbstractCommand
{
    use DryRun;

    protected function configure(): void
    {
        $type = $this->getType();

        $this
            ->setName(sprintf('pimcore:definition:import:%s', strtolower($type)))
            ->setAliases([sprintf('definition:import:%s', strtolower($type))])
            ->setDescription(sprintf('Import %s definition from a JSON export', $type))
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                sprintf('Path to %s JSON export file', $type)
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                sprintf('Force import (do not ask for confirmation when %s already exists)', $type)
            );

        $this->configureDryRunOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->getPath();
        $type = $this->getType();

        $name = $this->getDefinitionName(basename($path));
        if (null === $name) {
            throw new InvalidArgumentException('File name does not match expected format');
        }

        $json = $this->getJson($path);
        $force = $this->input->getOption('force');

        $logName = sprintf('(%s) <comment>%s</comment>', $type, $name);

        $definition = $this->loadDefinition($name);
        if (null !== $definition) {
            if ($force) {
                $this->output->writeln(sprintf('%s already exists', $logName));
            } else {
                if (!$this->askConfirmation($name)) {
                    return 0;
                }
            }
        } else {
            $this->output->writeln(sprintf('%s was not found', $logName));

            if ($this->isDryRun()) {
                $this->output->writeln($this->prefixDryRun(sprintf('Skipping creation of %s', $logName)));
            } else {
                $this->output->writeln(sprintf('Creating %s', $logName));
                $definition = $this->createDefinition($name);
            }
        }

        $result = false;
        if ($this->isDryRun()) {
            $this->output->writeln($this->prefixDryRun(sprintf('Skipping import of %s from %s', $logName, $path)));
            $result = true;
        } else {
            $this->output->writeln(sprintf('Importing %s from %s', $logName, $path));
            $result = $this->import($definition, $json);
        }

        if ($result) {
            $this->output->writeln(sprintf('Successfully imported %s', $logName));

            return 0;
        } else {
            $this->output->writeln(sprintf('<error>ERROR:</error> Failed to import %s', $logName));

            return 1;
        }
    }

    /**
     * Validate and return path to JSON file
     *
     */
    protected function getPath(): string
    {
        $path = $this->input->getArgument('path');
        if (!file_exists($path) || !is_readable($path)) {
            throw new InvalidArgumentException('File does not exist');
        }

        return $path;
    }

    /**
     * Load JSON data from file
     */
    protected function getJson(string $path): string
    {
        $content = file_get_contents($path);

        // try to decode json here as we want to fail early if file is no valid JSON
        json_decode($content, flags: JSON_THROW_ON_ERROR);

        // return string content as service import
        // methods decode JSON by their own
        return $content;
    }

    /**
     * Ask for confirmation before overwriting
     *
     *
     */
    protected function askConfirmation(string $name): bool
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            sprintf('(%s) <comment>%s</comment> already exists. Overwrite? [y/N] ', $this->getType(), $name),
            false
        );

        if ($helper->ask($this->input, $this->output, $question)) {
            return true;
        }

        return false;
    }

    /**
     * Get type
     *
     */
    abstract protected function getType(): string;

    /**
     * Get definition name from filename (e.g. class_Customer_export.json -> Customer)
     *
     *
     */
    abstract protected function getDefinitionName(string $filename): ?string;

    /**
     * Try to load definition by name
     *
     *
     */
    abstract protected function loadDefinition(string $name): ?ModelInterface;

    /**
     * Create a new definition
     *
     *
     */
    abstract protected function createDefinition(string $name): ?ModelInterface;

    /**
     * Process import
     *
     *
     */
    abstract protected function import(ModelInterface $definition, string $json): bool;
}
