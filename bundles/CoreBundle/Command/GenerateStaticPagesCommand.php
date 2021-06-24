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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\ClassDefinitionManager;
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
    protected StaticPageGenerator $staticPageGenerator;

    public function __construct(StaticPageGenerator $staticPageGenerator)
    {
        parent::__construct();

        $this->staticPageGenerator = $staticPageGenerator;
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:generate-static-pages')
            ->setDescription('Regenerate static Pages')
            ->addOption(
                'document-path',
                'd',
                InputOption::VALUE_REQUIRED,
                'Document Path to create the static sites from'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getOption('document-path');

        $listing = new Document\Listing();
        $listing->setCondition("type = 'page'");
        $listing->setOrderKey('id');
        $listing->setOrder('DESC');

        if ($path) {
            $parent = Document::getByPath($path);

            if (!$parent) {
                throw new \InvalidArgumentException(sprintf('Document with path %s not found', $path));
            }

            $listing->setCondition(
                "type = 'page' AND (id = :id OR path LIKE :path)",
                [
                    'id' => $parent->getId(),
                    'path' => $parent->getFullPath() . '/%',
                ]
            );
        }

        if ($listing->getTotalCount() > 0) {
            $progressBar = new ProgressBar($output, $listing->getTotalCount());
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

            $total = $listing->getTotalCount();
            $perLoop = 10;

            for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
                $listing->setLimit($perLoop);
                $listing->setOffset($i * $perLoop);

                /** @var Document\Page[] $pages */
                $pages = $listing->load();

                foreach ($pages as $page) {
                    if ($page->getStaticGeneratorEnabled()) {
                        $progressBar->setMessage(sprintf('Generate for Document "%s"', $page->getFullPath()));

                        $this->staticPageGenerator->generate($page);
                    }
                    else {
                        $progressBar->setMessage(sprintf('Skipping for Document "%s" cause Static Generation is disabled', $page->getFullPath()));

                        $this->staticPageGenerator->remove($page);
                    }

                    $progressBar->advance();
                }
            }

            $progressBar->finish();

            $output->writeln('');
            $output->writeln('<info>Finished generating static pages</info>');
        }
        else {
            $output->writeln('No Static Generation Pages found');
        }

        return 0;
    }
}
