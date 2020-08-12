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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Db;

class ClassLayoutDefinitionManager
{
    public const SAVED = 'saved';
    public const CREATED = 'created';
    public const DELETED = 'deleted';

    /**
     * Delete all custom layouts from db
     */
    public function cleanUpDeletedLayoutDefinitions(): array
    {
        $db = \Pimcore\Db::get();
        $layouts = $db->fetchAll('SELECT * FROM custom_layouts');
        $deleted = [];

        foreach ($layouts as $layout) {
            $id = $layout['id'];
            $name = $layout['name'];

            $cls = new CustomLayout();
            $cls->setId($id);
            $definitionFile = $cls->getDefinitionFile();

            if (!file_exists($definitionFile)) {
                $deleted[] = [$name, $id];

                //CustomLayout doesn't exist anymore, therefore we delete it
                $cls->delete();
            }
        }

        return $deleted;
    }

    /**
     * Updates all custom layouts from PIMCORE_CUSTOMLAYOUT_DIRECTORY
     */
    public function createOrUpdateLayoutDefinitions(): array
    {
        $customLayoutFolder = PIMCORE_CUSTOMLAYOUT_DIRECTORY;
        $files = glob($customLayoutFolder.'/*.php');

        $changes = [];

        foreach ($files as $file) {
            $layout = include $file;

            if ($layout instanceof CustomLayout) {
                $existingLayout = CustomLayout::getByNameAndClassId($layout->getName(), $layout->getClassId());

                if ($existingLayout instanceof CustomLayout) {
                    $changes[] = [$layout->getName(), $layout->getId(), self::SAVED];
                    $existingLayout->save(false);
                } else {
                    $changes[] = [$layout->getName(), $layout->getId(), self::CREATED];
                    $layout->save(false);
                }
            }
        }

        return $changes;
    }
}
