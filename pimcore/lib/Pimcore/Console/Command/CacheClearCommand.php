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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear caches')
            ->addOption(
                'tags', 't',
                InputOption::VALUE_OPTIONAL,
                "only specific tags (csv list of tags)"
            )
            ->addOption(
                'output', 'o',
                InputOption::VALUE_OPTIONAL,
                "only output cache"
            )
            ->addOption(
                'all', 'a',
                InputOption::VALUE_OPTIONAL,
                "clear all"
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption("tags")) {
            $tags = explode(",", $input->getOption("tags"));
            Cache::clearTags($tags);
        } else if ($input->getOption("output")) {
            Cache::clearTag("output");
        } else {
            Cache::clearAll();
        }
    }
}
