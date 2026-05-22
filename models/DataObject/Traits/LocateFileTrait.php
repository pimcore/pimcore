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

use RuntimeException;

/**
 * @internal
 */
trait LocateFileTrait
{
    /*
     * @throws RuntimeException
     */
    protected function locateDefinitionFile(string $key, string $pathTemplate): string
    {
        $customFile = sprintf(
            '%s/' . $pathTemplate,
            PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
            $key
        );

        $realCustomFile = realpath($customFile);

        if (
            $realCustomFile !== false &&
            is_file($realCustomFile) &&
            str_starts_with(
                $realCustomFile,
                realpath(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY)
            )
        ) {
            return $realCustomFile;
        }

        $defaultFile = sprintf(
            '%s/' . $pathTemplate,
            PIMCORE_CLASS_DEFINITION_DIRECTORY,
            $key
        );

        $realDefaultFile = realpath($defaultFile);

        if (
            $realDefaultFile !== false &&
            str_starts_with(
                $realDefaultFile,
                realpath(PIMCORE_CLASS_DEFINITION_DIRECTORY)
            )
        ) {
            return $realDefaultFile;
        }

        throw new RuntimeException('Invalid file path');
    }
}
