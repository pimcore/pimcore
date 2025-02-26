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

use Pimcore;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Snippet;
use Pimcore\Model\Factory;
use Pimcore\Resolver\ClassResolver;

trait Relation
{
    /**
     * @internal
     */
    protected function getPhpDocClassString(bool $asArray = false): string
    {
        $types = [];
        $factory = Pimcore::getContainer()->get('pimcore.model.factory');

        // add documents
        if ($this->getDocumentsAllowed()) {
            if ($documentTypes = $this->getDocumentTypes()) {
                $resolver = Pimcore::getContainer()->get('pimcore.class.resolver.document');
                foreach ($documentTypes as $item) {
                    if ($className = $this->resolveClassName($factory, $resolver, $item['documentTypes'])) {
                        if (str_starts_with($className, '\\') === false) {
                            $className = '\\' . $className;
                        }
                        $types[] = $className;
                    }
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
                $resolver = Pimcore::getContainer()->get('pimcore.class.resolver.asset');
                foreach ($assetTypes as $item) {
                    if ($className = $this->resolveClassName($factory, $resolver, $item['assetTypes'])) {
                        if (str_starts_with($className, '\\') === false) {
                            $className = '\\' . $className;
                        }
                        $types[] = $className;
                    }
                }
            } else {
                $types[] = '\\' . Asset::class;
            }
        }

        // add objects
        if ($this->getObjectsAllowed()) {
            if ($classes = $this->getClasses()) {
                $classMap = $factory->getClassMap();
                foreach ($classes as $item) {
                    /**
                     * Don´t use the factory method getClassNameFor here, because it will actually load the requested class and this could
                     * lead to problems during classes-rebuild command.
                     */
                    $className = sprintf('Pimcore\Model\DataObject\%s', ucfirst($item['classes']));
                    $className = $classMap[$className] ?? $className;

                    if (str_starts_with($className, '\\') === false) {
                        $className = '\\' . $className;
                    }

                    $types[] = $className;
                }
            } else {
                $types[] = '\\' . AbstractObject::class;
            }
        }

        if ($asArray) {
            $types = array_map(static fn (string $type): string => $type . '[]', $types);
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

    private function resolveClassName(Factory $factory, ClassResolver $resolver, string $type): ?string
    {
        if ($className = $resolver->resolve($type)) {
            try {
                return $factory->getClassNameFor($className);
            } catch (UnsupportedException) {
                return null;
            }
        }

        return null;
    }
}
