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

namespace Pimcore\Model\DataObject\ClassDefinition\Data\Extension;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Snippet;
use Pimcore\Model\Document\Page;

trait Relation
{
    /**
     * @internal
     */
    protected function getPhpDocClassString(bool $asArray = false): string
    {
        $types = [];

        // add documents
        if ($this->getDocumentsAllowed()) {
            if ($documentTypes = $this->getDocumentTypes()) {
                foreach ($documentTypes as $item) {
                    $types[] = sprintf('\Pimcore\Model\Document\%s', ucfirst($item['documentTypes']));
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
                    $types[] = sprintf('\Pimcore\Model\Asset\%s', ucfirst($item['assetTypes']));
                }
            } else {
                $types[] = '\\' . Asset::class;
            }
        }

        // add objects
        if ($this->getObjectsAllowed()) {
            if ($classes = $this->getClasses()) {
                foreach ($classes as $item) {
                    $types[] = sprintf('\Pimcore\Model\DataObject\%s', ucfirst($item['classes']));
                }
            } else {
                $types[] = '\\' . AbstractObject::class;
            }
        }

        if ($asArray) {
            $types = array_map(static fn(string $type): string => $type . '[]', $types);
        }

        return implode('|', $types);
    }

    /**
     * @return array<array{classes: string}>
     */
    public function getClasses(): array
    {
        return $this->classes ?: [];
    }

    /**
     * @return array<array{assetTypes: string}>
     */
    public function getAssetTypes(): array
    {
        return [];
    }

    /**
     * @return array<array{documentTypes: string}>
     */
    public function getDocumentTypes(): array
    {
        return [];
    }

    public function getDocumentsAllowed(): bool
    {
        return false;
    }

    public function getAssetsAllowed(): bool
    {
        return false;
    }

    public function getObjectsAllowed(): bool
    {
        return false;
    }
}
