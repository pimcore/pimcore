<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in 
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClassesRebuildCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('deployment:classes-rebuild')
            ->setDescription('rebuilds classes and db structure based on updated website/var/classes/*.psf files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->disableLogging();

        $list = new ClassDefinition\Listing();
        $list->load();

        foreach ($list->getClasses() as $class) {
            if ($output->isVerbose()) {
                $output->writeln($class->getName() . " [" . $class->getId() . "] saved");
            }

            $class->save();
        }
    }
}
