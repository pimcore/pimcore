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

namespace Pimcore\Bundle\CoreBundle\Command\Definition\Import;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\ModelInterface;

/**
 * @internal
 */
class ClassCommand extends AbstractStructureImportCommand
{
    /**
     * Get type
     *
     */
    protected function getType(): string
    {
        return 'Class';
    }

    /**
     * Get definition name from filename (e.g. class_Customer_export.json -> Customer)
     *
     *
     */
    protected function getDefinitionName(string $filename): ?string
    {
        $parts = [];
        if (1 === preg_match('/^class_(.*)_export\.json$/', $filename, $parts)) {
            return $parts[1];
        }

        return null;
    }

    /**
     * Try to load definition by name
     *
     *
     */
    protected function loadDefinition(string $name): ?ModelInterface
    {
        return ClassDefinition::getByName($name);
    }

    /**
     * Create a new definition
     *
     *
     */
    protected function createDefinition(string $name): ClassDefinition
    {
        $class = new ClassDefinition();
        $class->setName($name);

        return $class;
    }

    /**
     * Process import
     *
     *
     */
    protected function import(ModelInterface $definition, string $json): bool
    {
        if (!$definition instanceof ClassDefinition) {
            return false;
        }

        return Service::importClassDefinitionFromJson($definition, $json);
    }
}
