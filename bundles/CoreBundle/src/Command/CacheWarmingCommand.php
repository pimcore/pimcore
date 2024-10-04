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

namespace Pimcore\Bundle\CoreBundle\Command;

use InvalidArgumentException;
use Pimcore\Cache\Tool\Warming;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:cache:warming',
    description: 'Warm up caches'
)]
class CacheWarmingCommand extends AbstractCommand
{
    protected array $validTypes = [
        'document',
        'asset',
        'object',
    ];

    protected array $validDocumentTypes = [
        'page',
        'snippet',
        'folder',
        'link',
    ];

    protected array $validAssetTypes = [
        'archive',
        'audio',
        'document',
        'folder',
        'image',
        'text',
        'unknown',
        'video',
    ];

    protected array $validObjectTypes = [
        'object',
        'folder',
        'variant',
    ];

    protected function configure(): void
    {
        $this
            ->addOption(
                'types',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Perform warming only for this types of elements. Valid options: %s', $this->humanList($this->validTypes)),
                null
            )
            ->addOption(
                'documentTypes',
                'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of documents. Valid options: %s', $this->humanList($this->validDocumentTypes)),
                null
            )
            ->addOption(
                'assetTypes',
                'a',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of assets. Valid options: %s', $this->humanList($this->validAssetTypes)),
                null
            )
            ->addOption(
                'objectTypes',
                'o',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of objects. Valid options: %s', $this->humanList($this->validObjectTypes)),
                null
            )
            ->addOption(
                'classes',
                'c',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Restrict object warming to these classes (only valid for objects!). Valid options: class names of your classes defined in Pimcore',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('maintenance-mode')) {
            // set the timeout between each iteration to 0 if maintenance mode is on, because
            // we don't have to care about the load on the server
            Warming::setTimoutBetweenIteration(0);
        }

        try {
            $types = $this->getArrayOption('types', 'validTypes', 'type', true) ?? [];
            $documentTypes = $this->getArrayOption('documentTypes', 'validDocumentTypes', 'document type') ?? [];
            $assetTypes = $this->getArrayOption('assetTypes', 'validAssetTypes', 'asset type') ?? [];
            $objectTypes = $this->getArrayOption('objectTypes', 'validObjectTypes', 'object type') ?? [];
            $objectClasses = $this->input->getOption('classes') ?? [];
        } catch (InvalidArgumentException $e) {
            $this->writeError($e->getMessage());

            return 1;
        }

        if (in_array('document', $types)) {
            $this->writeWarmingMessage('document', $documentTypes);
            Warming::documents($documentTypes);
        }

        if (in_array('asset', $types)) {
            $this->writeWarmingMessage('asset', $assetTypes);
            Warming::assets($assetTypes);
        }

        if (in_array('object', $types)) {
            $extraInfo = count($objectClasses) ? ' from class: ' . implode(',', $objectClasses) : '';
            $this->writeWarmingMessage('object', $objectTypes, $extraInfo);
            Warming::objects($objectTypes, $objectClasses);
        }

        return 0;
    }

    protected function writeWarmingMessage(string $type, array $types, string $extra = ''): void
    {
        $output = sprintf('Warming <comment>%s</comment> cache', $type);
        if (null !== $types && count($types) > 0) {
            $output .= sprintf(' for types %s', $this->humanList($types, 'and', '<info>%s</info>'));
        } else {
            $output .= sprintf(' for <info>all</info> types');
        }

        if (!empty($extra)) {
            $output .= $extra;
        }

        $output .= '...';
        $this->output->writeln($output);
    }

    /**
     * A,B,C -> A, B or C (with an optional template for each item)
     *
     *
     */
    protected function humanList(array $list, string $glue = 'or', string $template = null): string
    {
        if (null !== $template) {
            array_walk($list, function (&$item) use ($template) {
                $item = sprintf($template, $item);
            });
        }

        if (count($list) > 1) {
            $lastElement = array_pop($list);

            return implode(', ', $list) . ' ' . $glue . ' ' . $lastElement;
        } else {
            return implode(', ', $list);
        }
    }

    /**
     * Get one of types, document, asset or object types, handle "all" value
     * and list input validation.
     *
     *
     */
    protected function getArrayOption(string $option, string $property, string $singular, bool $fallback = false): mixed
    {
        $input = $this->input->getOption($option);

        // fall back to whole list if fallback is set
        if (!$input || count($input) === 0) {
            if ($fallback) {
                $input = $this->$property;
            } else {
                $input = null;
            }
        }

        if (null !== $input) {
            foreach ($input as $value) {
                if (!in_array($value, $this->$property)) {
                    $message = sprintf('Invalid %s: %s', $singular, $value);

                    throw new InvalidArgumentException($message);
                }
            }
        }

        return $input;
    }
}
