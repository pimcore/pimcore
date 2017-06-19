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
use Pimcore\Console\Application;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Document\Tag\NamingStrategy\Migration\AbstractMigrationStrategy;
use Pimcore\Document\Tag\NamingStrategy\Migration\Exception\NameMappingException;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Model\Document;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

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
    private $validMigrationStrategies = ['render', 'analyze'];

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $updateQueries = [];

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
                'render'
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
            )
            ->addOption(
                'dump-sql', null,
                InputOption::VALUE_REQUIRED,
                'Dump SQL queries (pass stdout as value to print the queries)'
            )
            ->addOption(
                'clear-cache', 'C',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Clear the cache to start with a fresh cache (optionally pass a list of document IDs to remove only certain entries)'
            )
            ->addOption(
                'no-cache', null,
                InputOption::VALUE_NONE,
                'Do not load/save mapping results from/to cache'
            );

        $this->configureDryRunOption('Do not update editables. Just process and output name mapping');
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        $this->io->newLine();
        $this->io->writeln($formatter->formatBlock(
            'Welcome to the naming strategy migration command',
            'bg=blue;fg=white',
            true
        ));
        $this->io->newLine();

        $this->io->writeln('There are currently 2 different strategies which can be used to migrate your editables:');
        $this->io->newLine();

        $this->io->writeln([
            '  * <comment>render</comment>: renders all documents to fetch all editable names. To make the render strategy work',
            '    you must make sure that all your documents/templates can be rendered without errors.'
        ]);

        $this->io->newLine();

        $this->io->writeln([
            '  * <comment>analyze</comment>: analyzes the DB structure and tries to fetch editable names to migrate from the existing',
            '    editable names. As this can\'t always be reliably determined, you\'ll be prompted to resolve',
            '    potential conflicts. (<fg=red>experimental!</>).'
        ]);

        $this->io->newLine();

        $this->io->writeln([
            'The render strategy is less experimental but as all documents need to be rendered it can take up some time',
            'and it demands that all documents can be successfully rendered. The analyze strategy is way faster, but can\t',
            'resolve all conflicts automatically and demands understanding of your template/editable structure. You can',
            'try what works best for your project by simulating the migration with the --dry-run flag.',
            '',
            '<comment>In any case, please make sure you have a proper backup before running the migration!</comment>'
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $strategy = $this->io->choice(
            'Please select the naming strategy you want to use',
            $this->validMigrationStrategies,
            $input->getOption('strategy')
        );

        $input->setOption('strategy', $strategy);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // disable profiler for performance reasons (there are a LOT of DB queries being processed during this command)
        $this->getContainer()->get('profiler')->disable();

        $migrationStrategy = $this->getMigrationStrategy();

        $this->io->newLine();
        $this->io->writeln(sprintf(
            '  <comment>*</comment> Running migration with the <comment>%s</comment> migration strategy',
            $migrationStrategy->getName()
        ));

        $namingStrategy = $this->getNamingStrategy();

        $this->io->writeln(sprintf(
            '  <comment>*</comment> Migrating to the <comment>%s</comment> naming strategy',
            $namingStrategy->getName()
        ));

        $this->io->newLine();

        $this->cache = $this->getCache($migrationStrategy);

        if ($input->isInteractive()) {
            $this->io->newLine();
            $this->io->writeln('<comment>[WARNING]</comment> This action is is irreversible. Please make sure you have a proper backup!');
            if (!$this->io->confirm('Do you wish to continue?', false)) {
                return 0;
            }
        }

        $documents = $this->getDocuments($this->getDocumentIds());

        $this->io->newLine();
        $this->io->title(sprintf('[STEP 1] %s', $migrationStrategy->getStepDescription()));

        try {
            $migrationStrategy->initialize($this->io, $namingStrategy);
            $nameMapping = $migrationStrategy->getNameMapping(
                $documents,
                $this->cache
            );
        } catch (NameMappingException $e) {
            if ($e->getShowMessage()) {
                $this->io->error($e->getMessage());
            }

            return $e->getCode();
        }

        if (empty($nameMapping)) {
            $this->io->newLine();
            $this->io->success('Nothing to migrate.');

            return 0;
        }

        $this->io->newLine(3);
        $this->io->title('[STEP 2] Rename preflight...checking if none of the new tag names already exist in the DB');

        try {
            $nameMapping = $this->prepareRenames($nameMapping);
        } catch (\Exception $e) {
            $this->io->error(sprintf('Rename prerequisites failed. Error: %s', $e->getMessage()));

            return 4;
        }


        $this->io->newLine(3);
        $this->io->title('[STEP 3] Renaming editables to their new names');

        try {
            $this->processRenames($nameMapping);
        } catch (\Exception $e) {
            $this->io->newLine();
            $this->io->error($e->getMessage());
            $this->io->warning('All changes were rolled back. Please fix any problems and try again.');

            return 5;
        }

        $this->dumpQueries();

        $this->io->newLine(3);
        $this->io->success(sprintf(
            'Names were successfully migrated!' . PHP_EOL . PHP_EOL . 'Please reconfigure Pimcore now to use the "%s" naming strategy and clear the cache.',
            $namingStrategy->getName()
        ));
    }

    private function dumpQueries()
    {
        $dumpOption = $this->input->getOption('dump-sql');
        if (!$dumpOption) {
            return;
        }

        $this->io->newLine();
        $this->io->writeln('[SQL] Dumping SQL queries as --dump-sql option was passed');

        if ($dumpOption === 'stdout') {
            foreach ($this->updateQueries as $query) {
                $this->io->writeln($query);
            }
        } else {
            $fs       = new Filesystem();
            $tempfile = $fs->tempnam(sys_get_temp_dir(), 'migrate-sql-');

            $this->io->writeln(sprintf('[SQL] Dumping SQL queries to temp file <comment>%s</comment>', $tempfile));
            $fs->dumpFile($tempfile, implode(PHP_EOL, $this->updateQueries) . PHP_EOL);

            if (file_exists($dumpOption)) {
                $this->io->error(sprintf(
                    '[SQL] Can\'t move temp file to %s as file already exists',
                    $dumpOption
                ));

                return;
            }

            $this->io->writeln(sprintf('[SQL] Moving temp file to <comment>%s</comment>', $dumpOption));
            $fs->rename($tempfile, $dumpOption);
        }
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
            $this->io->simpleSection(sprintf('Checking document %d', $documentId));

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
        $db    = $this->getContainer()->get('database_connection');
        $query = 'UPDATE documents_elements SET name = :newName WHERE documentId = :documentId and name = :oldName';

        /** @var Statement $stmt */
        $stmt = null;
        if (!$this->isDryRun()) {
            $stmt = $db->prepare($query);
            $db->beginTransaction();
        }

        // sort by document ID
        ksort($nameMapping);

        try {
            foreach ($nameMapping as $documentId => $mapping) {
                $this->io->simpleSection(sprintf('Processing document %d', $documentId));

                // sort by old name
                ksort($mapping);

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

                    $this->updateQueries[] = str_replace([
                        ':documentId',
                        ':oldName',
                        ':newName',
                    ], [
                        $documentId,
                        sprintf('"%s"', $oldName),
                        sprintf('"%s"', $newName),
                    ], $query) . ';';

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

    private function getCache(AbstractMigrationStrategy $migrationStrategy): CacheInterface
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        /** @var Application $application */
        $application = $this->getApplication();
        $kernel      = $application->getKernel();

        $identifier = sprintf('editable-migration');
        $directory  = $kernel->getCacheDir() . '/' . $identifier;

        if ($this->io->getInput()->getOption('no-cache')) {
            $this->cache = new ArrayCache();
        } else {
            $this->cache = new FilesystemCache($migrationStrategy->getName(), 0, $directory);
        }

        $clearCache = $this->io->getInput()->getOption('clear-cache');
        if ((bool)$clearCache) {
            if (count($clearCache) === 1 && null === $clearCache[0]) {
                $this->io->comment('Clearing the cache');
                $this->cache->clear();
            } else {
                if (null !== $mapping = $this->cache->get('mapping')) {
                    foreach ($clearCache as $clearCacheId) {
                        if (!is_numeric($clearCacheId)) {
                            throw new \InvalidArgumentException('Invalid document ID "%s"', $clearCacheId);
                        }

                        $clearCacheId = (int)$clearCacheId;
                        if (isset($mapping[$clearCacheId])) {
                            $this->io->comment(sprintf('Deleting mapping for document <comment>%d</comment> from cache', $clearCacheId));
                            unset($mapping[$clearCacheId]);
                        }
                    }

                    $this->cache->set('mapping', $mapping);
                }
            }
        }

        return $this->cache;
    }

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

        return $strategy;
    }

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
}
