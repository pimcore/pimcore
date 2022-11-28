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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * @internal
 */
trait FieldcollectionObjectbrickDefinitionTrait
{
    /**
     * @var string|null
     */
    public $key;

    /**
     * @var string|null
     */
    public $parentClass;

    /**
     * Comma separated list of interfaces
     *
     * @var string|null
     */
    public $implementsInterfaces;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $group;

    /**
     * @var Layout|null
     */
    public $layoutDefinitions;

    /**
     * @var Data[]
     */
    protected $fieldDefinitions = [];

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     *
     * @return $this
     */
    public function setKey($key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getParentClass(): ?string
    {
        return $this->parentClass;
    }

    /**
     * @param string|null $parentClass
     *
     * @return $this
     */
    public function setParentClass($parentClass): static
    {
        $this->parentClass = $parentClass;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getLayoutDefinitions(): ?Layout
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param Layout|null $layoutDefinitions
     *
     * @return $this
     */
    public function setLayoutDefinitions($layoutDefinitions): static
    {
        $this->layoutDefinitions = $layoutDefinitions;

        $this->fieldDefinitions = [];
        $this->extractDataDefinitions($this->layoutDefinitions);

        return $this;
    }

    /**
     * @param array $context additional contextual data
     *
     * @return Data[]
     */
    public function getFieldDefinitions($context = [])
    {
        if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
            return $this->fieldDefinitions;
        }

        $enrichedFieldDefinitions = [];
        if (is_array($this->fieldDefinitions)) {
            foreach ($this->fieldDefinitions as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    /**
     * @param Data[] $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions(array $fieldDefinitions): static
    {
        $this->fieldDefinitions = $fieldDefinitions;

        return $this;
    }

    /**
     * @param string $key
     * @param Data $data
     *
     * @return $this
     */
    public function addFieldDefinition($key, $data): static
    {
        $this->fieldDefinitions[$key] = $data;

        return $this;
    }

    /**
     * @param string $key
     * @param array $context additional contextual data
     *
     * @return Data|null
     */
    public function getFieldDefinition($key, $context = []): ?Data
    {
        if (is_array($this->fieldDefinitions)) {
            $fieldDefinition = null;
            if (array_key_exists($key, $this->fieldDefinitions)) {
                $fieldDefinition = $this->fieldDefinitions[$key];
            } elseif (array_key_exists('localizedfields', $this->fieldDefinitions)) {
                /** @var Data\Localizedfields $lfDef */
                $lfDef = $this->fieldDefinitions['localizedfields'];
                $fieldDefinition = $lfDef->getFieldDefinition($key);
            }

            if ($fieldDefinition) {
                if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
                    return $fieldDefinition;
                }

                return $this->doEnrichFieldDefinition($fieldDefinition, $context);
            }
        }

        return null;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     *
     * @return $this
     */
    public function setGroup($group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getImplementsInterfaces(): ?string
    {
        return $this->implementsInterfaces;
    }

    public function setImplementsInterfaces(?string $implementsInterfaces): static
    {
        $this->implementsInterfaces = $implementsInterfaces;

        return $this;
    }

    /**
     * @internal
     *
     * @param Data $fieldDefinition
     * @param array $context
     *
     * @return mixed
     */
    abstract protected function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data;
}
