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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class GenerateStaticPagesCommand extends AbstractCommand
{
    public function __construct(protected StaticPageGenerator $staticPageGenerator)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:documents:generate-static-pages')
            ->setDescription('Regenerate static pages')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Document path prefix to create the static pages from'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getOption('path');

        $db = \Pimcore\Db::get();

        if ($path) {
            $parent = Document::getByPath(rtrim($path, '/'));

            if (!$parent) {
                throw new \InvalidArgumentException(sprintf('Document with path %s not found', $path));
            }

            $ids = $db->fetchFirstColumn('SELECT documents.id FROM `documents_page` LEFT JOIN documents ON documents_page.id = documents.id WHERE `staticGeneratorEnabled` = 1  AND (documents.id = :id OR path LIKE :path)', [
                'id' => $parent->getId(),
                'path' => $parent->getFullPath() . '/%',
            ]);
        } else {
            $ids = $db->fetchFirstColumn('SELECT id FROM `documents_page` WHERE `staticGeneratorEnabled` = 1');
        }

        $total = count($ids);

        if ($total) {
            $progressBar = new ProgressBar($output, $total);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

            foreach ($ids as $id) {
                $page = Document\Page::getById($id);
                if ($page->getStaticGeneratorEnabled()) {
                    $progressBar->setMessage(sprintf('Generate for document "%s"', $page->getFullPath()));

                    $this->staticPageGenerator->generate($page, ['is_cli' => true]);
                } else {
                    $progressBar->setMessage(sprintf('Skipping for document "%s" cause static generation is disabled', $page->getFullPath()));

                    $this->staticPageGenerator->remove($page);
                }

                $progressBar->advance();

                if ($progressBar->getProgress() % 10 === 0) {
                    \Pimcore::collectGarbage();
                }
            }

            $progressBar->finish();

            $output->writeln('');
            $output->writeln('<info>Finished generating static pages</info>');
        } else {
            $output->writeln('No static generation pages found');
        }

        return 0;
    }
}
