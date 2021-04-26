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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use Doctrine\Migrations\DependencyFactory;
use Pimcore\Console\AbstractCommand;
use Pimcore\Migrations\FilteredTableMetadataStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class InternalMigrationHelpersCommand extends AbstractCommand
{
    private FilteredTableMetadataStorage $metadataStorage;
    private DependencyFactory $dependencyFactory;

    public function __construct(DependencyFactory $dependencyFactory, FilteredTableMetadataStorage $metadataStorage, ?string $name = null)
    {
        parent::__construct($name);

        $this->metadataStorage = $metadataStorage;
        $this->dependencyFactory = $dependencyFactory;
    }

    protected function configure()
    {
        $this
            ->setHidden(true)
            ->setName('internal:migration-helpers')
            ->setDescription('For internal use only')
            ->addOption(
            'is-installed',
            null,
            InputOption::VALUE_NONE,
            'Checks whether Pimcore is already installed or not'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('is-installed')) {
            try {
                if (\Pimcore::isInstalled()) {
                    $this->metadataStorage->__invoke($this->dependencyFactory);
                    $this->metadataStorage->ensureInitialized();
                    $output->write(1);
                }
            } catch (\Throwable $e) {
                // nothing to do
            }
        }

        return 0;
    }
}
