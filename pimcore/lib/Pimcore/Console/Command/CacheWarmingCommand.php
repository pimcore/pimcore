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
        'folder',
        'link',
        'page',
        'snippet',
    ];

    /**
     * @var array
     */
    protected $validAssetTypes = [
        'folder',
        'image',
        'text',
        'audio',
        'video',
        'document',
        'archive',
        'unknown',
    ];

    /**
     * @var array
     */
    protected $validObjectTypes = [
        'archive',
        'audio',
        'document',
        'folder',
        'image',
        'text',
        'unknown',
        'video',
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
        // $this->enableMaintenanceMode();

        try {
            $types         = $this->getArrayOption('types', 'validTypes', 'type', true);
            $documentTypes = $this->getArrayOption('documentTypes', 'validDocumentTypes', 'document type');
            $assetTypes    = $this->getArrayOption('assetTypes', 'validAssetTypes', 'asset type');
            $objectTypes   = $this->getArrayOption('objectTypes', 'validObjectTypes', 'object type');
        } catch (\InvalidArgumentException $e) {
            $this->writeError($e->getMessage());
            return 1;
        }

        var_dump('TYPES');
        var_dump($types);

        if (in_array('document', $types)) {
            var_dump('DOCUMENT TYPES');
            var_dump($documentTypes);

            // Warming::documents($documentTypes);
        }

        if (in_array('asset', $types)) {
            var_dump('ASSET TYPES');
            var_dump($assetTypes);

            // Warming::assets($assetTypes);
        }

        if (in_array('object', $types)) {
            var_dump('OBJECT TYPES');
            var_dump($objectTypes);

            // Warming::assets($assetTypes);
        }

        // $this->disableMaintenanceMode();
    }

    /**
     * Get one of types, document types or object types and
     * handle "all" value and input validation.
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
     *
     * @throws \Exception
     */
    protected function enableMaintenanceMode()
    {
        // enable maintenance mode if requested
        if ($this->input->getOption('maintenanceMode')) {
            $maintenanceModeId = 'cache-warming-dummy-session-id';

            $this->output->isVerbose() && $this->output->writeln('--maintenanceMode option was passed...activating maintenance mode with ID <comment>%s</comment>...', $maintenanceModeId);

            Admin::activateMaintenanceMode($maintenanceModeId);

            // set the timeout between each iteration to 0 if maintenance mode is on, because
            //we don't have to care about the load on the server
            Warming::setTimoutBetweenIteration(0);
        }
    }

    protected function disableMaintenanceMode()
    {
        if ($this->input->getOption('maintenanceMode')) {
            $this->output->isVerbose() && $this->output->writeln('Deactivating maintenance mode...');
            Admin::deactivateMaintenanceMode();
        }
    }

    protected function humanList($list)
    {
        if (count($list) > 1) {
            $lastElement = array_pop($list);
            return implode(', ', $list) . ' or ' . $lastElement;
        } else {
            return implode(', ', $list);
        }
    }
}
