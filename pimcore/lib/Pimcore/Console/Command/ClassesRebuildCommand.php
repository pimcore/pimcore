<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
