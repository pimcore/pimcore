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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model;

class Layout implements Model\DataObject\ClassDefinition\Data\VarExporterInterface
{
    use Model\DataObject\ClassDefinition\Helper\VarExport {
        __set_state as private _VarExport__set_state;
    }

    /**
     * @internal
     */
    public ?string $name = null;

    /**
     * @internal
     */
    public ?string $type = null;

    /**
     * @internal
     */
    public ?string $region = null;

    /**
     * @internal
     */
    public ?string $title = null;

    /**
     * @internal
     */
    public string|int|null $width = 0;

    /**
     * @internal
     */
    public string|int|null $height = 0;

    /**
     * @internal
     *
     */
    public bool $collapsible = false;

    /**
     * @internal
     */
    public bool $collapsed = false;

    /**
     * @internal
     */
    public ?string $bodyStyle = null;

    /**
     * @internal
     *
     */
    public string $datatype = 'layout';

    /**
     * @internal
     */
    public array|string|null $permissions;

    /**
     * @internal
     */
    public array $children = [];

    /**
     * @internal
     */
    public bool $locked = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getWidth(): int|string|null
    {
        return $this->width;
    }

    public function getHeight(): int|string|null
    {
        return $this->height;
    }

    public function getCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function getPermissions(): array|string
    {
        return $this->permissions;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return $this
     */
    public function setWidth(int|string|null $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @return $this
     */
    public function setHeight(int|string|null $height): static
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCollapsible(bool $collapsible): static
    {
        $this->collapsible = $collapsible;

        $this->filterCollapsibleValue();

        return $this;
    }

    /**
     * @return $this
     */
    public function setPermissions(array|string $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @internal
     */
    public function &getChildrenByRef(): array
    {
        return $this->children;
    }

    /**
     * @return $this
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function hasChildren(): bool
    {
        if (count($this->children) > 0) {
            return true;
        }

        return false;
    }

    /**
     * typehint "mixed" is required for asset-metadata-definitions bundle
     * since it doesn't extend Core Data Types
     *
     * @param Data|Layout $child
     */
    public function addChild(mixed $child): void
    {
        $this->children[] = $child;
    }

    /**
     * @return $this
     */
    public function setValues(array $data = [], array $blockedKeys = []): static
    {
        foreach ($data as $key => $value) {
            if (isset($value) && !in_array($key, $blockedKeys)) {
                $method = 'set' . ucfirst($key);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this;
    }

    public function getDatatype(): string
    {
        return $this->datatype;
    }

    /**
     * @return $this
     */
    public function setDatatype(string $datatype): static
    {
        $this->datatype = $datatype;

        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @return $this
     */
    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = $collapsed;

        $this->filterCollapsibleValue();

        return $this;
    }

    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }

    /**
     * @return $this
     */
    public function setBodyStyle(string $bodyStyle): static
    {
        $this->bodyStyle = $bodyStyle;

        return $this;
    }

    public function getBodyStyle(): ?string
    {
        return $this->bodyStyle;
    }

    /**
     * @return $this
     */
    protected function filterCollapsibleValue(): static
    {
        //if class definition set as collapsed the code below forces collapsible, issue: #778
        $this->collapsible = $this->getCollapsed() || $this->getCollapsible();

        return $this;
    }

    public function getBlockedVarsForExport(): array
    {
        return ['blockedVarsForExport'];
    }

    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        foreach ($this->getBlockedVarsForExport() as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }
}
