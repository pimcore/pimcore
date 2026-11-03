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

use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\VerificationFinding;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:verify',
    description: 'Check the documents of a converted Classification Store against their checksums',
    aliases: ['classificationstore:verify']
)]
class VerifyClassificationStoreCommand extends AbstractCommand
{
    public function __construct(private readonly DocumentEngineMigrationService $migrationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'class',
            null,
            InputOption::VALUE_REQUIRED,
            'Only check this class, instead of every class'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classId = $input->getOption('class');
        $classId = is_string($classId) && $classId !== '' ? $classId : null;
        $classes = $this->migrationService->getClasses($classId);

        if ($classes === [] && $classId !== null) {
            $this->io->error('No class definition found for ' . $classId);

            return self::FAILURE;
        }

        $failed = false;

        foreach ($classes as $class) {
            // the findings are written as they are found rather than collected first: a class whose
            // documents were all written before checksums were stamped has one per stored group
            $report = $this->migrationService->verify(
                $class,
                function (VerificationFinding $finding, string $message) use ($class): void {
                    $this->io->writeln($finding === VerificationFinding::Unproven
                        ? sprintf('<comment>%s unproven</comment>: %s', $class->getId(), $message)
                        : sprintf('<error>%s</error>: %s', $class->getId(), $message));
                }
            );

            $failed = $failed || $report->hasFailed();
        }

        if ($failed) {
            $this->io->error('The documents of at least one class did not pass.');

            return self::FAILURE;
        }

        $this->io->success('Every document matches its checksum and no EAV rows survive.');

        return self::SUCCESS;
    }
}
