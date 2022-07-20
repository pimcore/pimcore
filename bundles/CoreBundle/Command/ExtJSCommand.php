<?php

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

namespace Pimcore\Bundle\CoreBundle\Command;

use MatthiasMullie\Minify\JS;
use Pimcore\Console\AbstractCommand;
use Pimcore\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class ExtJSCommand extends AbstractCommand
{
    protected static $defaultName = 'pimcore:extjs';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:extjs')
            ->setHidden(true)
            ->setDescription('Regenerate minified ext-js file')
            ->addArgument(
                'src',
                InputOption::VALUE_REQUIRED,
                'manifest file'
            )
            ->addArgument(
                'dest',
                InputOption::VALUE_REQUIRED,
                'destination file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');

        if (!$src) {
            $src = 'dev/pimcore/pimcore/bundles/AdminBundle/Resources/public/extjs/js/pimcore-ext-all.json';
        }

        if (!$dest) {
            $dest = 'dev/pimcore/pimcore/bundles/AdminBundle/Resources/public/extjs/js/ext-all';
        }

        $absoluteManifest = getcwd() . '/' . $src;

        $bootstrapFile = getcwd() . '/dev/pimcore/pimcore/bundles/AdminBundle/Resources/public/extjs/js/bootstrap-ext-all.js';
        $bootstrap = file_get_contents($bootstrapFile);
        if (!$bootstrap) {
            throw new \Exception('bootstrap file not found');
        }

        $scriptContents = $bootstrap . "\n\n";
        $scriptContentsMinified = $bootstrap . "\n\n";

        if (is_file($absoluteManifest)) {
            $manifestContents = file_get_contents($absoluteManifest);
            $manifestContents = json_decode($manifestContents, true);

            $loadOrder = $manifestContents['loadOrder'];

            $count = 0;

            // build dependencies
            $main = $loadOrder[count($loadOrder) - 1];
            $list = [
                $main['idx'] => $main,
            ];

            $this->populate($loadOrder, $list, $main);
            ksort($list);

            // replace this with loadOrder if we want to load the entire list
            foreach ($loadOrder as $loadOrderIdx => $loadOrderItem) {
                $count++;
                $relativePath = $loadOrderItem['path'];

                $fullPath = PIMCORE_WEB_ROOT . $relativePath;

                if (is_file($fullPath)) {
                    $includeContents = file_get_contents($fullPath);

                    $minify = new JS($includeContents);
                    $includeContentsMinfified = $minify->minify();
                    $includeContentsMinfified .= "\r\n;\r\n";
                    $scriptContentsMinified .= $includeContentsMinfified;

                    $includeContents .= "\r\n;\r\n";
                    $scriptContents .= $includeContents;
                } else {
                    throw new \Exception('file does not exist: ' . $fullPath);
                }
            }
        } else {
            throw new \Exception('manifest does not exist: ' . $absoluteManifest);
        }

        $scriptPath = getcwd() . '/' . $dest;
        file_put_contents($scriptPath . '.js', $scriptContentsMinified);
        file_put_contents($scriptPath . '-debug.js', $scriptContents);

        $io->writeln('writing to ' . $scriptPath);

        $io->success('Done');

        return 0;
    }

    public function populate($loadOrder, &$list, $item)
    {
        $depth = count(debug_backtrace());
        if ($depth > 100) {
            Logger::error('Depth: ' . $depth);
        }

        if (is_array($item['requires'])) {
            foreach ($item['requires'] as $itemId) {
                if (isset($list[$itemId])) {
                    continue;
                }
                $subItem = $loadOrder[$itemId];
                $list[$itemId] = $subItem;
                $this->populate($loadOrder, $list, $subItem);
            }
        }

        if (is_array($item['uses'])) {
            foreach ($item['uses'] as $itemId) {
                if (isset($list[$itemId])) {
                    continue;
                }
                $subItem = $loadOrder[$itemId];
                $list[$itemId] = $subItem;
                $this->populate($loadOrder, $list, $subItem);
            }
        }
    }
}
