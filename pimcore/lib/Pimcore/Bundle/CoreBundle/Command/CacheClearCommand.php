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
                'tags',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only specific tags (csv list of tags)'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_NONE,
                'Only output cache'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Clear all'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('tags')) {
            $tags = $this->prepareTags($input->getOption('tags'));
            Cache::clearTags($tags);
        } elseif ($input->getOption('output')) {
            Cache::clearTag('output');
        } else {
            Cache::clearAll();
        }
    }

    private function prepareTags(array $tags): array
    {
        // previous implementations didn't use VALUE_IS_ARRAY and just supported csv strings, so we not iterate
        // through the input array and split values
        // -t foo -t bar,baz results in [foo, bar, baz]
        $result = [];
        foreach ($tags as $tagEntries) {
            $tagEntries = explode(',', $tagEntries);
            foreach ($tagEntries as $tagEntry) {
                $tagEntry = trim($tagEntry);

                if (!empty($tagEntry)) {
                    $result[] = $tagEntry;
                }
            }
        }

        return $result;
    }
}
