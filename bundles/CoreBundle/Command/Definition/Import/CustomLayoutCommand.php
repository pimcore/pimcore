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

namespace Pimcore\Bundle\CoreBundle\Command\Definition\Import;

use Pimcore\Logger;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Symfony\Component\Console\Input\InputOption;

class CustomLayoutCommand extends AbstractStructureImportCommand
{
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'class-name',
            'c',
            InputOption::VALUE_REQUIRED,
            'Object class name which is used for custom definition'
        );
    }

    /**
     * Get type.
     *
     * @return string
     */
    protected function getType()
    {
        return 'Customlayout';
    }

    /**
     * Get definition name from filename (e.g. custom_definition_Customer_export.json -> Customer).
     *
     * @param string $filename
     *
     * @return string|null
     */
    protected function getDefinitionName($filename)
    {
        $parts = [];
        if (preg_match('/^custom_definition_(.*)_export\.json$/', $filename, $parts) === 1) {
            return $parts[1];
        }

        return null;
    }

    /**
     * Try to load definition by name.
     *
     * @param string $name
     *
     * @throws \Exception
     *
     * @return null|AbstractModel
     */
    protected function loadDefinition($name)
    {
        return CustomLayout::getByName($name);
    }

    /**
     * @param string $name
     *
     * @return AbstractModel
     */
    protected function createDefinition($name)
    {
        $className = $this->input->getOption('class-name');
        if ($className) {
            $class = DataObject\ClassDefinition::getByName($className);
            if ($class != null) {
                return CustomLayout::create(
                    [
                        'classId' => $class->getId(),
                        'name' => $name,
                    ]
                );
            }
        }

        return null;
    }

    /**
     * @param AbstractModel|CustomLayout|null $customLayout
     * @param string|null $json
     *
     * @return bool
     */
    protected function import(AbstractModel $customLayout = null, $json = null)
    {
        if ($customLayout == null) {
            return false;
        }

        $importData = json_decode($json, true);

        try {
            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($importData['layoutDefinitions'], true);

            $customLayout->setLayoutDefinitions($layout);
            $customLayout->setDescription($importData['description']);
            $customLayout->setUserModification(0);
            $customLayout->setUserOwner(0);

            $customLayout->save();

            return true;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }

        return false;
    }
}
