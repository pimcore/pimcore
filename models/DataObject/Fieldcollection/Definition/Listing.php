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

namespace Pimcore\Model\DataObject\Fieldcollection\Definition;

use Pimcore\Model\DataObject\Fieldcollection\Definition;

class Listing
{
    /**
     * @return Definition[]
     */
    public function load(): array
    {
        $fields = [];

        $files = $this->loadFileNames();
        foreach ($files as $file) {
            $fields[] = include $file;
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function loadNames(): array
    {
        $fields = [];

        $files = $this->loadFileNames();
        foreach ($files as $file) {
            $fields[] = basename($file, '.php');
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function loadFileNames(): array
    {
        $filenames = [];

        $fieldCollectionFolders = array_filter(array_unique(array_map('realpath', [
            PIMCORE_CLASS_DEFINITION_DIRECTORY . '/fieldcollections',
            PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY . '/fieldcollections',
        ])));

        foreach ($fieldCollectionFolders as $fieldCollectionFolder) {
            $files = glob($fieldCollectionFolder . '/*.php');
            foreach ($files as $file) {
                $realFile = realpath($file);
                if ($realFile) {
                    $filenames[] = $realFile;
                }
            }
        }

        return array_unique($filenames);
    }
}
