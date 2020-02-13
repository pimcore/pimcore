<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\ClassDefinition\Data;

trait FieldcollectionObjectbrickDefinitionTrait
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $parentClass;

    /**
     * Comma separated list of interfaces
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
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @var Data[]
     */
    protected $fieldDefinitions;


    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentClass()
    {
        return $this->parentClass;
    }

    /**
     * @param string $parentClass
     *
     * @return $this
     */
    public function setParentClass($parentClass)
    {
        $this->parentClass = $parentClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return array
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param array $layoutDefinitions
     *
     * @return $this
     */
    public function setLayoutDefinitions($layoutDefinitions)
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
     * @param array $fieldDefinitions
     *
     * @return $this
     */
    public function setFieldDefinitions($fieldDefinitions)
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
    public function addFieldDefinition($key, $data)
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
    public function getFieldDefinition($key, $context = [])
    {
        if (is_array($this->fieldDefinitions) && array_key_exists($key, $this->fieldDefinitions)) {
            if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
                return $this->fieldDefinitions[$key];
            }

            return $this->doEnrichFieldDefinition($this->fieldDefinitions[$key], $context);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImplementsInterfaces(): ?string
    {
        return $this->implementsInterfaces;
    }


    /**
     * @param string|null $implementsInterfaces
     * @return $this
     */
    public function setImplementsInterfaces(?string $implementsInterfaces)
    {
        $this->implementsInterfaces = $implementsInterfaces;
        return $this;
    }
}
