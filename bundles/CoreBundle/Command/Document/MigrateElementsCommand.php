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

namespace Pimcore\Bundle\CoreBundle\Command\Document;

use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Pimcore\Model\Version;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateElementsCommand extends AbstractCommand
{
    /**
     * @var bool
     */
    private $runCommand = true;

    protected function configure()
    {
        $this
            ->setName('pimcore:documents:migrate-elements')
            ->setDescription('Migrates document elements to editables. See issue https://github.com/pimcore/pimcore/issues/7384 first');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)// :int
    {
        if (!$this->runCommand) {
            return 0;
        }

        $db = Db::get();
        $versionsRaw = $db->fetchAllAssociative("SELECT v.id AS vId, d.id as dId, d.key as `dKey` FROM versions v, documents d WHERE ctype = 'document' AND v.cid = d.`id` AND (d.`type`  = 'snippet' OR d.`type` = 'page')");

        foreach ($versionsRaw as $versionRaw) {
            $this->processVersionRow($versionRaw);
        }

        return 0;
    }

    private function processVersionRow(array $row)
    {
        $vId = $row['vId'];
        $documentId = $row['dId'];
        $dKey = $row['dKey'];
        $this->output->writeln(sprintf('processing version %d, document id: %d, document key: %s', $vId, $documentId, $dKey));
        $version = Version::getById($vId);
        $version->loadData();
        $version->save();
        $this->output->writeln(sprintf('saved version %d, document id: %d, document key: %s', $vId, $documentId, $dKey));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $this->output->writeln('<error>WARNING:</error> This command is potentially dangerous. Please use with caution and make sure you have a proper backup! '
            . 'See issue https://github.com/pimcore/pimcore/issues/7384 first');
        $this->io->newLine();
        $question = new ConfirmationQuestion('Do you want to continue? (y/n) ', false);

        $this->runCommand = $helper->ask($input, $output, $question);

        $this->io->newLine();
    }
}
