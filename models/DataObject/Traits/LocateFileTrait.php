<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Traits;

/**
 * @internal
 */
trait LocateFileTrait
{
    protected function locateDefinitionFile(string $key, string $pathTemplate): string
    {
        $customFile = sprintf('%s/' . $pathTemplate, PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY, $key);

        if (is_file($customFile)) {
            return $customFile;
        }

        return sprintf('%s/' . $pathTemplate, PIMCORE_CLASS_DEFINITION_DIRECTORY, $key);
    }

    protected function locateFile(string $key, string $pathTemplate): string
    {
        $customFile = sprintf('%s/' . $pathTemplate, PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY, $key);

        if (is_file($customFile)) {
            return $customFile;
        }

        return sprintf('%s/' . $pathTemplate, PIMCORE_CLASS_DIRECTORY, $key);
    }
}
