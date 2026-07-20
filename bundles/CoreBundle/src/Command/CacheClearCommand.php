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

use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Event\SystemEvents;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:cache:clear',
    description: 'Clear caches'
)]
class CacheClearCommand extends AbstractCommand
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        if ($input->getOption('tags')) {
            $tags = $this->prepareTags($input->getOption('tags'));
            Cache::clearTags($tags);
            $io->success('Pimcore data cache cleared tags: ' . implode(',', $tags));
        } elseif ($input->getOption('output')) {
            Cache::clearTag('output');
            $io->success('Pimcore output cache cleared successfully');
        } else {
            Cache::clearAll();

            $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR);

            $io->success('Pimcore data cache cleared successfully');
        }

        return 0;
    }

    /**
     * @param string[] $tags
     *
     * @return string[]
     */
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
