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

namespace Pimcore\Console\Command\Definition\Import;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\ClassDefinition\Service;

class ClassCommand extends AbstractStructureImportCommand
{
    /**
     * Get type
     *
     * @return string
     */
    protected function getType()
    {
        return 'Class';
    }

    /**
     * Get definition name from filename (e.g. class_Customer_export.json -> Customer)
     *
     * @param string $filename
     * @return string
     */
    protected function getDefinitionName($filename)
    {
        $parts = [];
        if (1 === preg_match('/^class_(.*)_export\.json$/', $filename, $parts)) {
            return $parts[1];
        }
    }

    /**
     * Try to load definition by name
     *
     * @param $name
     * @return AbstractModel|null
     */
    protected function loadDefinition($name)
    {
        return ClassDefinition::getByName($name);
    }

    /**
     * Create a new definition
     *
     * @param $name
     * @return AbstractModel
     */
    protected function createDefinition($name)
    {
        $class = new ClassDefinition();
        $class->setName($name);

        return $class;
    }

    /**
     * Process import
     *
     * @param AbstractModel $definition
     * @param string $json
     * @return bool
     */
    protected function import(AbstractModel $definition, $json)
    {
        return Service::importClassDefinitionFromJson($definition, $json);
    }
}
