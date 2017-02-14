<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command\Definition\Import;

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\AbstractModel;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractStructureImportCommand extends AbstractCommand
{
    use DryRun;

    /**
     *
     */
    protected function configure()
    {
        $type = $this->getType();

        $this
            ->setName(sprintf('definition:import:%s', strtolower($type)))
            ->setDescription(sprintf('Import %s definition from a JSON export', $type))
            ->addArgument(
                'path', InputArgument::REQUIRED,
                sprintf('Path to %s JSON export file', $type)
            )
            ->addOption(
                'force', 'f', InputOption::VALUE_NONE,
                sprintf('Force import (do not ask for confirmation when %s already exists)', $type)
            );

        $this->configureDryRunOption();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getPath();
        $type = $this->getType();

        $name = $this->getDefinitionName(basename($path));
        if (null === $name) {
            throw new \InvalidArgumentException('File name does not match expected format');
        }

        $json  = $this->getJson($path);
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
     * @return string
     */
    protected function getPath()
    {
        $path = $this->input->getArgument('path');
        if (!file_exists($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('File does not exist');
        }

        return $path;
    }

    /**
     * Load JSON data from file
     *
     * @param $path
     * @return mixed
     */
    protected function getJson($path)
    {
        $content = file_get_contents($path);

        // try to decode json here as we want to fail early if file is no valid JSON
        $json = json_decode($content);
        if (null === $json) {
            throw new \InvalidArgumentException('JSON could not be decoded');
        }

        // return string content as service import
        // methods decode JSON by their own
        return $content;
    }

    /**
     * Ask for confirmation before overwriting
     *
     * @param $name
     * @return bool
     */
    protected function askConfirmation($name)
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
     * @return string
     */
    abstract protected function getType();

    /**
     * Get definition name from filename (e.g. class_Customer_export.json -> Customer)
     *
     * @param string $filename
     * @return string
     */
    abstract protected function getDefinitionName($filename);

    /**
     * Try to load definition by name
     *
     * @param $name
     * @return AbstractModel|null
     */
    abstract protected function loadDefinition($name);

    /**
     * Create a new definition
     *
     * @param $name
     * @return AbstractModel
     */
    abstract protected function createDefinition($name);

    /**
     * Process import
     *
     * @param AbstractModel $definition
     * @param string $json
     * @return bool
     */
    abstract protected function import(AbstractModel $definition, $json);
}
