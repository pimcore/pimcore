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

namespace Pimcore\Bundle\CoreBundle\Command\Targeting;

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Document\Tag\NamingStrategy\NestedNamingStrategy;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateElementNamesCommand extends AbstractCommand
{
    /**
     * @var Db\ConnectionInterface
     */
    private $db;

    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var array
     */
    private $updates = [];

    /**
     * @var bool
     */
    private $runCommand = true;

    public function __construct(NamingStrategyInterface $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:targeting:migrate-element-names')
            ->setDescription('Migrates targeting element names to new prefixed format. Works only with nested naming strategy.')
            ->addOption(
                'document', 'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Document ID(s) to process. Defaults to all documents if option is omitted.'
            )
            ->addOption(
                'ignoreDocument',
                'D',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Document ID(s) to ignore'
            )
            ->addOption(
                'dry-run', 'N',
                InputOption::VALUE_NONE,
                'Simulate only'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $this->output->writeln('<error>WARNING:</error> This command is potentially dangerous. Please use with caution and make sure you have a proper backup!');
        $this->output->writeln('Use the <comment>--dry-run</comment> option to preview what would be done.');
        $this->io->newLine();

        $question = new ConfirmationQuestion('Do you want to continue? (y/n) ', false);

        $this->runCommand = $helper->ask($input, $output, $question);

        $this->io->newLine();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->runCommand) {
            return 0;
        }

        if (!$this->namingStrategy instanceof NestedNamingStrategy) {
            $this->io->error('Migration is only supported for the nested naming strategy');

            return 1;
        }

        $this->db = Db::get();

        $qb = $this->buildQuery();
        $stmt = $qb->execute();

        while ($row = $stmt->fetch()) {
            $this->processRow($row);
        }

        $this->processUpdates();

        return 0;
    }

    private function buildQuery(): QueryBuilder
    {
        $qb = $this->db->createQueryBuilder();
        $qb
            ->select('documentId', 'name', 'type')
            ->from('documents_elements');

        $documentIds = $this->input->getOption('document');
        if (!empty($documentIds)) {
            $qb->where($qb->expr()->in('documentId', $documentIds));
        }

        $ignoredIds = $this->input->getOption('ignoreDocument');
        if (!empty($ignoredIds)) {
            $qb->where($qb->expr()->notIn('documentId', $ignoredIds));
        }

        return $qb;
    }

    private function processRow(array $row)
    {
        $pattern = '/';
        $pattern .= preg_quote(TargetingDocumentInterface::TARGET_GROUP_ELEMENT_PREFIX, '/');
        $pattern .= '(\d+)';
        $pattern .= preg_quote(TargetingDocumentInterface::TARGET_GROUP_ELEMENT_SUFFIX, '/');
        $pattern .= '/';

        if (preg_match($pattern, $row['name'], $match)) {
            $newName = preg_replace($pattern, '', $row['name']); // remove all targeting prefixes
            $newName = $match[0] . $newName; // add the first matched prefix at the beginning

            if ($newName === $row['name']) {
                return;
            }

            $updateRow = [
                'documentId' => $row['documentId'],
                'type' => $row['type'],
                'oldName' => $row['name'],
                'newName' => $newName,
            ];

            $this->output->writeln(sprintf(
                '[DOC <info>%d</info>] Renaming element <comment>%s</comment> (type: <comment>%s</comment>) to <comment>%s</comment>',
                $updateRow['documentId'],
                $updateRow['oldName'],
                $updateRow['type'],
                $updateRow['newName']
            ));

            $this->updates[] = $updateRow;
        }
    }

    private function processUpdates()
    {
        if (empty($this->updates)) {
            $this->output->writeln('<info>SUCCESS:</info> Nothing found to update');

            return;
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf('Found <comment>%d</comment> elements to rename', count($this->updates)));
        $this->output->writeln('');

        $sql = 'UPDATE documents_elements SET name = :newName WHERE documentId = :documentId AND name = :oldName AND type = :type';

        if ($this->input->getOption('dry-run')) {
            $this->output->writeln('<fg=cyan>DRY-RUN:</> Would execute the following updates:');
            $this->output->writeln('');

            foreach ($this->updates as $row) {
                $this->output->writeln(sprintf(
                    'UPDATE documents_elements SET %s WHERE %s AND %s AND %s;',
                    $this->db->quoteInto('name = ?', $row['newName']),
                    $this->db->quoteInto('documentId = ?', $row['documentId']),
                    $this->db->quoteInto('name = ?', $row['oldName']),
                    $this->db->quoteInto('type = ?', $row['type'])
                ));
            }

            return;
        }

        $this->db->beginTransaction();

        $stmt = $this->db->prepare($sql);

        try {
            foreach ($this->updates as $row) {
                $this->output->writeln(sprintf(
                    '[DOC <info>%d</info>] Renaming element <comment>%s</comment> (type: <comment>%s</comment>) to <comment>%s</comment>',
                    $row['documentId'],
                    $row['oldName'],
                    $row['type'],
                    $row['newName']
                ));

                $stmt->execute($row);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();

            $this->output->writeln('');
            $this->output->writeln(sprintf('<error>ERROR:</error> %s', $e->getMessage()));

            return;
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf('<info>SUCCESS:</info> All <comment>%s</comment> updates were processed', count($this->updates)));
    }
}
