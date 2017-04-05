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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Command;

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
            ->setName('pimcore:cache:clear')
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

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption("tags")) {
            $tags = explode(",", $input->getOption("tags"));
            Cache::clearTags($tags);
        } elseif ($input->getOption("output")) {
            Cache::clearTag("output");
        } else {
            Cache::clearAll();
        }
    }
}
