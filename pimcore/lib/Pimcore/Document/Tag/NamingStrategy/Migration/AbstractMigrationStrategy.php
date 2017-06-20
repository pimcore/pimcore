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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Tag\NamingStrategy\Migration;

use Pimcore\Bundle\CoreBundle\Command\Document\MigrateTagNamingStrategyCommand;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Model\Document;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractMigrationStrategy
{
    /**
     * @var PimcoreStyle
     */
    protected $io;

    /**
     * @var MigrateTagNamingStrategyCommand
     */
    protected $command;

    /**
     * @var NamingStrategyInterface
     */
    protected $namingStrategy;

    /**
     * @var bool
     */
    protected $initialized = false;

    public function initialize(
        MigrateTagNamingStrategyCommand $command,
        NamingStrategyInterface $namingStrategy
    )
    {
        if ($this->initialized) {
            throw new \LogicException('Strategy is already initialized');
        }

        $this->command        = $command;
        $this->io             = $command->getIo();
        $this->namingStrategy = $namingStrategy;

        $this->initializeEnvironment();

        $this->initialized = true;
    }

    protected function initializeEnvironment()
    {
    }

    abstract public function getName(): string;

    abstract public function getStepDescription(): string;

    /**
     * @param \Generator|Document\PageSnippet[] $documents
     *
     * @return array
     */
    abstract public function getNameMapping(\Generator $documents): array;

    /**
     * @param array $mapping
     * @param MappingError[] $errors
     *
     * @return array
     */
    protected function removeMappingForErroredDocuments(array $mapping, array $errors): array
    {
        // do not migrate any element in errored documents
        foreach (array_keys($errors) as $documentId) {
            //
            if (isset($mapping[$documentId])) {
                unset($mapping[$documentId]);
            }
        }

        return $mapping;
    }

    /**
     * @param MappingError[] $errors
     * @param string $title
     * @param string $description
     * @param string $question
     *
     * @return bool
     */
    protected function confirmProceedAfterRenderingErrors(array $errors, string $title, string $description, string $question): bool
    {
        $messages = [];
        foreach ($errors as $documentId => $error) {
            $messages[] = sprintf(
                '<comment>%s</comment> (ID <info>%d</info>): %s',
                $error->getDocumentPath(),
                $error->getDocumentId(),
                $error->getException()->getMessage()
            );
        }

        $this->io->writeln($title);
        $this->io->writeln('');

        $this->io->listing($messages);

        $this->io->writeln('');
        $this->io->writeln($description);

        /** @var QuestionHelper $helper */
        $helper   = $this->command->getHelper('question');
        $question = new ConfirmationQuestion(
            $question . ' (y/n) ',
            false
        );

        return $helper->ask($this->io->getInput(), $this->io->getOutput(), $question);
    }
}
