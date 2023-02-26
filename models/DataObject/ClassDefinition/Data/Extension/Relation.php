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

use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Model\Document\TypeDefinition\Loader\TypeLoader as DocumentTypeLoader;
use function Symfony\Component\String\u;

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
                    $class[] = $this->getClassName('document', $item['documentTypes']) . $strArray;
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
                    $class[] = $this->getClassName('asset', $item['assetTypes']) . $strArray;
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
                    $class[] = $this->getClassName('object', $item['classes']) . $strArray;
                }
            }
        }

        return $class;
    }

    /**
     * @param 'asset'|'document'|'object' $type
     */
    private function getClassName(string $type, string $shortName): string
    {
        $typeLoader = match ($type) {
            'document' => \Pimcore::getContainer()->get(DocumentTypeLoader::class),
            default => null,
        };

        if ($typeLoader) {
            try {
                return u($typeLoader->getClassNameFor($shortName))->ensureStart('\\');
            } catch (UnsupportedException) {
                // try next
            }
        }

        $factory = \Pimcore::getContainer()->get('pimcore.model.factory');
        $className = match ($type) {
            'asset' => '\Pimcore\Model\Asset\\' . ucfirst($shortName),
            'document' => '\Pimcore\Model\Document\\' . ucfirst($shortName),
            'object' => '\Pimcore\Model\DataObject\\' . ucfirst($shortName),
        };

        try {
            return u($factory->getClassNameFor($className))->ensureStart('\\');
        } catch (UnsupportedException) {
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
