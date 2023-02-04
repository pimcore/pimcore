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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

trait Relation
{
    /**
     * @internal
     *
     * @param bool $asArray
     *
     * @return string[]
     */
    protected function getPhpDocClassString($asArray = false)
    {
        // init
        $class = [];
        $strArray = $asArray ? '[]' : '';

        // add documents
        if ($this->getDocumentsAllowed()) {
            $documentTypes = $this->getDocumentTypes();
            if (count($documentTypes) == 0) {
                $class[] = '\Pimcore\Model\Document\Page' . $strArray;
                $class[] = '\Pimcore\Model\Document\Snippet' . $strArray;
                $class[] = '\Pimcore\Model\Document' . $strArray;
            } elseif (is_array($documentTypes)) {
                foreach ($documentTypes as $item) {
                    $class[] = $this->getMappedClassName('\Pimcore\Model\Document\\' . ucfirst($item['documentTypes'])) . $strArray;
                }
            }
        }

        // add asset
        if ($this->getAssetsAllowed()) {
            $assetTypes = $this->getAssetTypes();
            if (count($assetTypes) == 0) {
                $class[] = '\Pimcore\Model\Asset' . $strArray;
            } elseif (is_array($assetTypes)) {
                foreach ($assetTypes as $item) {
                    $class[] = $this->getMappedClassName('\Pimcore\Model\Asset\\' . ucfirst($item['assetTypes'])) . $strArray;
                }
            }
        }

        // add objects
        if ($this->getObjectsAllowed()) {
            $classes = $this->getClasses();
            if (count($classes) === 0) {
                $class[] = '\Pimcore\Model\DataObject\AbstractObject' . $strArray;
            } elseif (is_array($classes)) {
                foreach ($classes as $item) {
                    $class[] = $this->getMappedClassName('\Pimcore\Model\DataObject\\' . ucfirst($item['classes'])) . $strArray;
                }
            }
        }

        return $class;
    }

    protected function getMappedClassName(string $className): string
    {
        try {
            $className = \Pimcore::getContainer()->get('pimcore.model.factory')->getClassNameFor($className);
            if ($className[0] !== '\\') {
                $className = '\\' . $className;
            }
        } finally {
            return $className;
        }
    }

    /**
     * @return array[
     *  'classes' => string,
     * ]
     */
    public function getClasses()
    {
        return $this->classes ?: [];
    }

    /**
     * @return array[
     *  'assetTypes' => string,
     * ]
     */
    public function getAssetTypes()
    {
        return [];
    }

    /**
     * @return array[
     *  'documentTypes' => string,
     * ]
     */
    public function getDocumentTypes()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function getDocumentsAllowed()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getAssetsAllowed()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getObjectsAllowed()
    {
        return false;
    }
}
