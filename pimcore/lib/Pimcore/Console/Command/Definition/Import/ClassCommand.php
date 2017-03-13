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
