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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Command\IndexService;


use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Tool\IndexUpdater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BootstrapCommand extends AbstractIndexServiceCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ecommerce:indexservice:bootstrap')
            ->setDescription('Bootstrap tasks creating/updating index (for all tenants)')
            ->addOption('create-or-update-index-structure', null, InputOption::VALUE_NONE, 'Whether to create or update the index structure')
            ->addOption('update-index', null, InputOption::VALUE_NONE, 'Whether to rebuild the index')
            ->addOption('object-list-class', null, InputOption::VALUE_REQUIRED, 'The object list class to use', 'Object\\Product\\Listing')
            ->addOption('list-condition', null, InputOption::VALUE_OPTIONAL, 'An optional condition for object list', '');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updateIndex = $input->getOption('update-index');
        $createOrUpdateIndexStructure = $input->getOption('create-or-update-index-structure');
        $objectListClass = $input->getOption('object-list-class');
        $listCondition = $input->getOption('list-condition');

        if ($createOrUpdateIndexStructure && $updateIndex) {
            // create/update structure and update index
            IndexUpdater::updateIndex($objectListClass, $listCondition, true, self::LOGGER_NAME);
        } elseif ($createOrUpdateIndexStructure) {
            // just create/update structure
            Factory::getInstance()->getIndexService()->createOrUpdateIndexStructures();
        } elseif ($updateIndex) {
            // just update index
            IndexUpdater::updateIndex($objectListClass, $listCondition, false, self::LOGGER_NAME);
        } else {
            throw new \Exception("At least one option (--create-or-update-index-structure or --update-index) needs to be given");
        }
    }

}