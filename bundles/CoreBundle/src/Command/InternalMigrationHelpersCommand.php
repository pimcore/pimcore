<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use Doctrine\Migrations\DependencyFactory;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Migrations\FilteredTableMetadataStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @internal
 */
#[AsCommand(
    name: 'internal:migration-helpers',
    description: 'For internal use only',
    hidden: true
)]
class InternalMigrationHelpersCommand extends AbstractCommand
{
    public function __construct(private DependencyFactory $dependencyFactory, private FilteredTableMetadataStorage $metadataStorage, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'is-installed',
                null,
                InputOption::VALUE_NONE,
                'Checks whether Pimcore is already installed or not'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('is-installed')) {
            try {
                if (Pimcore::isInstalled()) {
                    $this->metadataStorage->__invoke($this->dependencyFactory);
                    $this->metadataStorage->ensureInitialized();
                    $output->write('1');
                }
            } catch (Throwable $e) {
                // nothing to do
            }
        }

        return 0;
    }
}
