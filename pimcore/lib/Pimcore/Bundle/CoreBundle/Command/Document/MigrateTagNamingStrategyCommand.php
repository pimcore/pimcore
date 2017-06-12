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

namespace Pimcore\Bundle\CoreBundle\Command\Document;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Document\Tag\NamingStrategy\Migration\AbstractMigrationStrategy;
use Pimcore\Document\Tag\NamingStrategy\Migration\Exception\NameMappingException;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Model\Document;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateTagNamingStrategyCommand extends AbstractCommand
{
    use DryRun;

    /**
     * @var array
     */
    private $validDocumentTypes = [
        'page',
        'snippet',
        'email',
        'printpage'
    ];

    /**
     * @var array
     */
    private $validMigrationStrategies = [
        'analyze', 'render'
    ];

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:documents:migrate-naming-strategy')
            ->setDescription('Migrates document editables to nested naming strategy')
            ->addOption(
                'strategy', 's',
                InputOption::VALUE_REQUIRED,
                sprintf('The migration strategy to use. Available strategies: %s', implode(', ', $this->validMigrationStrategies)),
                'analyze'
            )
            ->addOption(
                'document', 'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Document ID(s) to process. Defaults to all documents if option is omitted.'
            )
            ->addOption(
                'ignoreDocument', 'D',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Document ID(s) to ignore'
            )
            ->addOption(
                'user', 'u',
                InputOption::VALUE_REQUIRED,
                'Run command under given user name (only needed for the render migration strategy)',
                'admin'
            );

        $this->configureDryRunOption('Do not update editables. Just process and output name mapping');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrationStrategy = $this->getMigrationStrategy();
        $namingStrategy    = $this->getNamingStrategy();

        $documents = $this->getDocuments($this->getDocumentIds());

        $this->io->writeln(PHP_EOL);
        $this->io->title(sprintf('[STEP 1] %s', $migrationStrategy->getStepDescription()));

        try {
            $migrationStrategy->initialize($this, $namingStrategy);
            $nameMapping = $migrationStrategy->getNameMapping($documents);
        } catch (NameMappingException $e) {
            if ($e->getShowMessage()) {
                $this->io->error($e->getMessage());
            }

            return $e->getCode();
        }

        if (empty($nameMapping)) {
            $this->io->writeln('');
            $this->io->success('Nothing to migrate.');

            return 0;
        }

        $this->io->writeln(PHP_EOL . PHP_EOL);
        $this->io->title('[STEP 2] Rename preflight...checking if none of the new tag names already exist in the DB');

        try {
            $nameMapping = $this->prepareRenames($nameMapping);
        } catch (\Exception $e) {
            $this->io->error(sprintf('Rename prerequisites failed. Error: %s', $e->getMessage()));

            return 4;
        }


        $this->io->writeln(PHP_EOL . PHP_EOL);
        $this->io->title('[STEP 3] Renaming editables to their new names');

        try {
            $this->processRenames($nameMapping);
        } catch (\Exception $e) {
            $this->io->writeln('');
            $this->io->error($e->getMessage());
            $this->io->warning('All changes were rolled back. Please fix any problems and try again.');

            return 5;
        }

        $this->io->writeln(PHP_EOL . PHP_EOL);
        $this->io->success(sprintf(
            'Names were successfully migrated!' . PHP_EOL . PHP_EOL . 'Please reconfigure Pimcore now to use the "%s" naming strategy and clear the cache.',
            $namingStrategy->getName()
        ));
    }

    /**
     * Tests if renames can safely be done. Check if old names exist in the DB and if the new name does not. If elements
     * do not exist for the current document (inheritance), the will be ignored.
     *
     * @param array $nameMapping
     *
     * @return array
     */
    private function prepareRenames(array $nameMapping): array
    {
        $db   = $this->getContainer()->get('database_connection');
        $stmt = $db->prepare('SELECT documentId, name FROM documents_elements WHERE documentId = :documentId AND name = :name');

        // keep a blacklist of all not existing elements (inheritance)
        $blacklist = [];

        foreach ($nameMapping as $documentId => $mapping) {
            $this->writeSimpleSection(sprintf('Checking document %d', $documentId));

            foreach ($mapping as $oldName => $newName) {
                $this->io->writeln(sprintf(
                    'Checking rename from <info>%s</info> to <info>%s</info>',
                    $oldName,
                    $newName
                ));

                // check the old editable exists in the DB
                $oldResult = $stmt->execute([
                    'documentId' => $documentId,
                    'name'       => $oldName
                ]);

                if (!$oldResult) {
                    throw new \RuntimeException(sprintf(
                        'Failed to load old editable (document ID: %d, name: %s)',
                        $documentId,
                        $oldName
                    ));
                }

                if (count($stmt->fetchAll()) === 0) {
                    $this->io->writeln(sprintf('<comment>%s</comment>' . PHP_EOL, sprintf(
                        'WARNING: Ignoring old editable (document ID: %d, name: %s) as it was not found (probably due to inheritance)',
                        $documentId,
                        $oldName
                    )));

                    $blacklist[$documentId][] = $oldName;
                }

                // check if there is no new editable
                $newResult = $stmt->execute([
                    'documentId' => $documentId,
                    'name'       => $newName
                ]);

                if (!$newResult) {
                    throw new \RuntimeException(sprintf(
                        'Failed to query for new editable existence (document ID: %d, name: %s)',
                        $documentId,
                        $newName
                    ));
                }

                if (count($stmt->fetchAll()) !== 0) {
                    throw new \RuntimeException(sprintf(
                        'New editable already exists in the DB (document ID: %d, name: %s)',
                        $documentId,
                        $newName
                    ));
                }
            }

            $this->output->writeln('');
        }

        // unset blacklisted names which were not found in the DB (inheritance)
        foreach ($blacklist as $documentId => $blacklistedNames) {
            foreach ($blacklistedNames as $blacklistedName) {
                unset($nameMapping[$documentId][$blacklistedName]);
            }

            if (empty($nameMapping[$documentId])) {
                unset($nameMapping[$documentId]);
            }
        }

        return $nameMapping;
    }

    /**
     * Updates elements with their new names
     *
     * @param array $nameMapping
     *
     * @throws \Exception
     */
    private function processRenames(array $nameMapping)
    {
        $db = $this->getContainer()->get('database_connection');

        /** @var Statement $stmt */
        $stmt = null;
        if (!$this->isDryRun()) {
            $stmt = $db->prepare('UPDATE documents_elements SET name = :newName WHERE documentId = :documentId and name = :oldName');
            $db->beginTransaction();
        }

        try {
            foreach ($nameMapping as $documentId => $mapping) {
                $this->writeSimpleSection(sprintf('Processing document %d', $documentId));

                foreach ($mapping as $oldName => $newName) {
                    $message = sprintf(
                        'Renaming editable <info>%s</info> to <info>%s</info>',
                        $oldName,
                        $newName
                    );

                    if ($this->isDryRun()) {
                        $message = $this->prefixDryRun($message, '[DRY-RUN]');
                    } else {
                        $message = '  <comment>*</comment> ' . $message;
                    }

                    $this->io->writeln($message);

                    if ($this->isDryRun()) {
                        continue;
                    }

                    $result = $stmt->execute([
                        'documentId' => $documentId,
                        'oldName'    => $oldName,
                        'newName'    => $newName,
                    ]);

                    if (!$result) {
                        throw new \RuntimeException(sprintf(
                            'Failed to update name from %s to %s for document %d',
                            $oldName,
                            $newName,
                            $documentId
                        ));
                    }
                }
            }

            if (!$this->isDryRun()) {
                $db->commit();
            }
        } catch (\Exception $e) {
            if (!$this->isDryRun()) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @return AbstractMigrationStrategy
     */
    private function getMigrationStrategy(): AbstractMigrationStrategy
    {
        $container = $this->getContainer();

        $strategyName = $this->input->getOption('strategy');
        $strategyId   = 'pimcore.document.tag.naming.migration.strategy.' . $strategyName;

        if (!$container->has($strategyId)) {
            throw new \InvalidArgumentException(sprintf(
                'The migration strategy "%s" does not exist',
                $strategyName
            ));
        }

        /** @var AbstractMigrationStrategy $strategy */
        $strategy = $container->get($strategyId);

        $this->io->comment(sprintf(
            'Running migration with the <comment>%s</comment> strategy',
            $strategy->getName()
        ));

        return $strategy;
    }

    /**
     * Loads nested naming strategy and checks if it is not the same as the currently configured one
     *
     * @return NamingStrategyInterface
     */
    private function getNamingStrategy(): NamingStrategyInterface
    {
        $container = $this->getContainer();

        /** @var NamingStrategyInterface $strategy */
        $strategy           = $container->get('pimcore.document.tag.naming.strategy.nested');
        $configuredStrategy = $container->get('pimcore.document.tag.naming.strategy');

        if ($strategy === $configuredStrategy) {
            throw new \LogicException(sprintf(
                'The strategy "%s" is already configured. You can\'t migrate to the same strategy as the configured one.',
                $strategy->getName()
            ));
        }

        return $strategy;
    }

    /**
     * Gets document IDs and filters ignored ones
     *
     * @return array
     */
    private function getDocumentIds(): array
    {
        $documentIds = $this->input->getOption('document');
        if (empty($documentIds)) {
            // load all documents if no IDs were passed as option
            $documentIds = $this->getAllDocumentIds();
        }

        $ignoredIds = $this->input->getOption('ignoreDocument');
        if (!empty($ignoredIds)) {
            $documentIds = array_filter($documentIds, function ($id) use ($ignoredIds) {
                return !in_array($id, $ignoredIds);
            });
        }

        return $documentIds;
    }

    /**
     * Returns all document IDs for documents matching valid types
     *
     * @return array
     */
    private function getAllDocumentIds(): array
    {
        $db = $this->getContainer()->get('database_connection');
        $qb = $db->createQueryBuilder();
        $qb
            ->select('id')
            ->from('documents')
            ->where('type IN (:validTypes)');

        $qb->setParameter('validTypes', $this->validDocumentTypes, Connection::PARAM_STR_ARRAY);

        $stmt   = $qb->execute();
        $result = $stmt->fetchAll();

        $documentIds = array_map(function ($id) {
            $id = (int)$id;

            if ($id <= 0) {
                throw new \RuntimeException(sprintf('Invalid ID: %d', $id));
            }

            return $id;
        }, array_column($result, 'id'));

        return $documentIds;
    }

    /**
     * Loads documents for configured document IDs
     *
     * @param int[] $documentIds
     *
     * @return \Generator|Document[]
     */
    private function getDocuments(array $documentIds): \Generator
    {
        foreach ($documentIds as $documentId) {
            $document = Document::getById($documentId);

            if (!$document || !$document instanceof Document\PageSnippet) {
                throw new \InvalidArgumentException(sprintf('Invalid document: %d', $documentId));
            }

            if (!in_array($document->getType(), $this->validDocumentTypes)) {
                throw new \InvalidArgumentException(sprintf(
                    'Document "%s" ("%d") has no valid type',
                    $document->getRealFullPath(),
                    $document->getId()
                ));
            }

            yield $document;
        }
    }

    /**
     * Console helper to output an underlined title without prepending block and/or formatting output
     *
     * @param string $message
     * @param string $underlineChar
     * @param string|null $style
     */
    public function writeSimpleSection(string $message, string $underlineChar = '-', string $style = null)
    {
        $underline = str_repeat($underlineChar, Helper::strlenWithoutDecoration($this->io->getFormatter(), $message));

        if (null !== $style) {
            $format    = '<%s>%s</>';
            $message   = sprintf($format, $style, $message);
            $underline = sprintf($format, $style, $underline);
        }

        $this->io->writeln([
            '',
            $message,
            $underline,
            ''
        ]);
    }

    /**
     * Exposes IO for migration strategies
     *
     * @return PimcoreStyle
     */
    public function getIo(): PimcoreStyle
    {
        return $this->io;
    }
}
