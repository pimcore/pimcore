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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * @internal
 */
trait FieldcollectionObjectbrickDefinitionTrait
{
    use FieldDefinitionEnrichmentModelTrait;

    public ?string $key = null;

    public ?string $parentClass = null;

    /**
     * Comma separated list of interfaces
     */
    public ?string $implementsInterfaces = null;

    public ?string $title = null;

    public ?string $group = null;

    public ?Layout $layoutDefinitions = null;

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return $this
     */
    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getParentClass(): ?string
    {
        return $this->parentClass;
    }

    /**
     * @return $this
     */
    public function setParentClass(?string $parentClass): static
    {
        $this->parentClass = $parentClass;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getLayoutDefinitions(): ?Layout
    {
        return $this->layoutDefinitions;
    }

    /**
     * @return $this
     */
    public function setLayoutDefinitions(?Layout $layoutDefinitions): static
    {
        if ($layoutDefinitions) {
            $this->layoutDefinitions = $layoutDefinitions;

            $this->setFieldDefinitions(null);
            $this->extractDataDefinitions($this->layoutDefinitions);
        }

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getImplementsInterfaces(): ?string
    {
        return $this->implementsInterfaces;
    }

    /**
     * @return $this
     */
    public function setImplementsInterfaces(?string $implementsInterfaces): static
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }
}
