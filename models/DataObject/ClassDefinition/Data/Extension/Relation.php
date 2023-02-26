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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Snippet;
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
        $types = [];

        // add documents
        if ($this->getDocumentsAllowed()) {
            if ($documentTypes = $this->getDocumentTypes()) {
                foreach ($documentTypes as $item) {
                    $types[] = $this->getClassName('document', $item['documentTypes']);
                }
            } else {
                $types[] = '\\' . Page::class;
                $types[] = '\\' . Snippet::class;
                $types[] = '\\' . Document::class;
            }
        }

        // add assets
        if ($this->getAssetsAllowed()) {
            if ($assetTypes = $this->getAssetTypes()) {
                foreach ($assetTypes as $item) {
                    $types[] = $this->getClassName('asset', $item['assetTypes']);
                }
            } else {
                $types[] = '\\' . Asset::class;
            }
        }

        // add objects
        if ($this->getObjectsAllowed()) {
            if ($classes = $this->getClasses()) {
                foreach ($classes as $item) {
                    $types[] = $this->getClassName('object', $item['classes']);
                }
            } else {
                $types[] = '\\' . AbstractObject::class;
            }
        }

        if ($asArray) {
            $types = array_map(static fn (string $type): string => $type . '[]', $types);
        }

        return $types;
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

            try {
                return u($typeLoader->build($shortName)::class)->ensureStart('\\');
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
