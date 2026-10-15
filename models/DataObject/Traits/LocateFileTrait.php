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
    // @throws RuntimeException
    protected function locateDefinitionFile(string $key, string $pathTemplate): string
    {
        if (str_contains($key, '/') || str_contains($key, '\\') || str_contains($key, '..')) {
            throw new RuntimeException('Invalid key');
        }

        $customBase = realpath(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY);

        if ($customBase !== false) {
            $customFile = sprintf('%s/' . $pathTemplate, $customBase, $key);

            if (is_file($customFile)) {
                return $customFile;
            }
        }

        $defaultBase = realpath(PIMCORE_CLASS_DEFINITION_DIRECTORY);

        if ($defaultBase === false) {
            throw new RuntimeException('Invalid file path');
        }

        return sprintf('%s/' . $pathTemplate, $defaultBase, $key);
    }

    // @throws RuntimeException
    protected function locateFile(string $key, string $pathTemplate): string
    {
        if (str_contains($key, '/') || str_contains($key, '\\') || str_contains($key, '..')) {
            throw new RuntimeException('Invalid key');
        }

        $customBase = realpath(PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY);

        if ($customBase !== false) {
            $customFile = sprintf('%s/' . $pathTemplate, $customBase, $key);

            if (is_file($customFile)) {
                return $customFile;
            }
        }

        $defaultBase = realpath(PIMCORE_CLASS_DIRECTORY);

        if ($defaultBase === false) {
            throw new RuntimeException('Invalid file path');
        }

        return sprintf('%s/' . $pathTemplate, $defaultBase, $key);
    }
}
