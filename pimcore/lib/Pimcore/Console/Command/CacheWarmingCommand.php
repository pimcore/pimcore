<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Console\Command;

use Pimcore\Cache\Tool\Warming;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Admin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command implementation of cache-warming.php
 */
class CacheWarmingCommand extends AbstractCommand
{
    /**
     * @var array
     */
    protected $validTypes = [
        'document',
        'asset',
        'object',
    ];

    /**
     * @var array
     */
    protected $validDocumentTypes = [
        'page',
        'snippet',
        'folder',
        'link',
    ];

    /**
     * @var array
     */
    protected $validAssetTypes = [
        'archive',
        'audio',
        'document',
        'folder',
        'image',
        'text',
        'unknown',
        'video',
    ];

    /**
     * @var array
     */
    protected $validObjectTypes = [
        'object',
        'folder',
        'variant',
    ];

    protected function configure()
    {
        $this
            ->setName('cache:warming')
            ->setDescription('Warm up caches')
            ->addOption(
                'types', 't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Perform warming only for this types of elements. Valid options: %s', $this->humanList($this->validTypes)),
                null
            )
            ->addOption(
                'documentTypes', 'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of documents. Valid options: %s', $this->humanList($this->validDocumentTypes)),
                null
            )
            ->addOption(
                'assetTypes', 'a',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of assets. Valid options: %s', $this->humanList($this->validAssetTypes)),
                null
            )
            ->addOption(
                'objectTypes', 'o',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                sprintf('Restrict warming to these types of objects. Valid options: %s', $this->humanList($this->validObjectTypes)),
                null
            )
            ->addOption(
                'classes', 'c',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Restrict object warming to these classes (only valid for objects!). Valid options: class names of your classes defined in Pimcore',
                null
            )
            ->addOption(
                'maintenanceMode', 'm',
                InputOption::VALUE_NONE,
                'Enable maintenance mode during cache warming'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->initializePimcoreLogging();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->enableMaintenanceMode();

        try {
            $types         = $this->getArrayOption('types', 'validTypes', 'type', true);
            $documentTypes = $this->getArrayOption('documentTypes', 'validDocumentTypes', 'document type');
            $assetTypes    = $this->getArrayOption('assetTypes', 'validAssetTypes', 'asset type');
            $objectTypes   = $this->getArrayOption('objectTypes', 'validObjectTypes', 'object type');
        } catch (\InvalidArgumentException $e) {
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
            $this->writeWarmingMessage('object', $objectTypes);
            Warming::assets($assetTypes);
        }

        $this->disableMaintenanceMode();
    }

    protected function writeWarmingMessage($type, $types)
    {
        $output = sprintf('Warming <comment>%s</comment> cache', $type);
        if (null !== $types && count($types) > 0) {
            $output .= sprintf(' for types %s', $this->humanList($types, 'and', '<info>%s</info>'));
        } else {
            $output .= sprintf(' for <info>all</info> types');
        }

        $output .= '...';
        $this->output->writeln($output);
    }

    /**
     * A,B,C -> A, B or C (with an optional template for each item)
     *
     * @param $list
     * @param string $glue
     * @param null $template
     * @return string
     */
    protected function humanList($list, $glue = 'or', $template = null)
    {
        if (null !== $template) {
            array_walk($list, function(&$item) use ($template) {
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
     * @param $option
     * @param $property
     * @param $singular
     * @param bool $fallback
     * @return mixed
     */
    protected function getArrayOption($option, $property, $singular, $fallback = false)
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
                    throw new \InvalidArgumentException($message);
                }
            }
        }

        return $input;
    }

    /**
     * Enable maintenance mode if --maintenanceMode option was passed
     */
    protected function enableMaintenanceMode()
    {
        // enable maintenance mode if requested
        if ($this->input->getOption('maintenanceMode')) {
            $maintenanceModeId = 'cache-warming-dummy-session-id';

            $this->output->writeln('Activating maintenance mode with ID <comment>%s</comment>...', $maintenanceModeId);

            Admin::activateMaintenanceMode($maintenanceModeId);

            // set the timeout between each iteration to 0 if maintenance mode is on, because
            // we don't have to care about the load on the server
            Warming::setTimoutBetweenIteration(0);
        }
    }

    protected function disableMaintenanceMode()
    {
        if ($this->input->getOption('maintenanceMode')) {
            $this->output->writeln('Deactivating maintenance mode...');
            Admin::deactivateMaintenanceMode();
        }
    }
}
