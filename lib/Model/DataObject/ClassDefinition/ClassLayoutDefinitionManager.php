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

namespace Pimcore\Model\DataObject\ClassDefinition;

/**
 * @deprecated will be removed in Pimcore 11
 */
class ClassLayoutDefinitionManager
{
    public const SAVED = 'saved';

    public const CREATED = 'created';

    public const DELETED = 'deleted';

    /**
     * @deprecated
     *
     * Delete all custom layouts from db
     */
    public function cleanUpDeletedLayoutDefinitions(): array
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. It will be removed in Pimcore 11. Custom Layouts are not managed in db anymore.', __METHOD__)
        );

        return [];
    }

    /**
     * @deprecated
     *
     * Updates all custom layouts from PIMCORE_CUSTOMLAYOUT_DIRECTORY
     */
    public function createOrUpdateLayoutDefinitions(): array
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. It will be removed in Pimcore 11. Custom Layouts are not managed in db anymore.', __METHOD__)
        );

        $customLayoutFolder = PIMCORE_CUSTOMLAYOUT_DIRECTORY;
        $files = glob($customLayoutFolder.'/*.php');

        $changes = [];

        foreach ($files as $file) {
            $layout = include $file;

            if ($layout instanceof CustomLayout) {
                $existingLayout = CustomLayout::getByNameAndClassId($layout->getName(), $layout->getClassId());

                if ($existingLayout instanceof CustomLayout) {
                    $changes[] = [$layout->getName(), $layout->getId(), self::SAVED];
                    $existingLayout->save();
                } else {
                    $changes[] = [$layout->getName(), $layout->getId(), self::CREATED];
                    $layout->save();
                }
            }
        }

        return $changes;
    }
}
