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

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Asset;

class InternalVideoConverterCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:video-converter')
            ->setDescription('For internal use only')
            ->addArgument("processId");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Asset\Video\Thumbnail\Processor::execute($input->getArgument("processId"));
    }
}
