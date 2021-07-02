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

namespace Pimcore\Bundle\CoreBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class Version20210608094532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates class definition files';
    }

    public function up(Schema $schema): void
    {
        $this->rebuildClassesCommand();
    }

    public function down(Schema $schema): void
    {
        $this->rebuildClassesCommand();
    }

    /**
     * @throws \Exception|ProcessFailedException
     */
    private function rebuildClassesCommand()
    {
        $cmd = [
            Console::getPhpCli(),
            'bin/console',
            'pimcore:deployment:classes-rebuild',
            '--create-classes',
        ];

        $process = new Process($cmd);
        $process->setWorkingDirectory(PIMCORE_PROJECT_ROOT);
        $process->mustRun();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if ($process->getOutput())  {
            $this->write($process->getOutput());
        }
    }
}
