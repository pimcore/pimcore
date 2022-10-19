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
     *
     * @var string
     */
    public string $name;

    /**
     * @internal
     *
     * @var string
     */
    public string $type;

    /**
     * @internal
     *
     * @var string
     */
    public string $region;

    /**
     * @internal
     *
     * @var string
     */
    public string $title;

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $height = 0;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $collapsible = false;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $collapsed = false;

    /**
     * @internal
     *
     * @var string
     */
    public string $bodyStyle;

    /**
     * @internal
     *
     * @var string
     */
    public string $datatype = 'layout';

    /**
     * @internal
     *
     * @var array
     */
    public array $permissions;

    /**
     * @internal
     *
     * @var array
     */
    public array $children = [];

    /**
     * @internal
     *
     * @var bool
     */
    public bool $locked = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int|string
     */
    public function getWidth(): int|string
    {
        return $this->width;
    }

    /**
     * @return int|string
     */
    public function getHeight(): int|string
    {
        return $this->height;
    }

    /**
     * @return bool
     */
    public function getCollapsible(): bool
    {
        return $this->collapsible;
    }

    /**
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $region
     *
     * @return $this
     */
    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param int|string $width
     *
     * @return $this
     */
    public function setWidth(int|string $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @param int|string $height
     *
     * @return $this
     */
    public function setHeight(int|string $height): static
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @param bool $collapsible
     *
     * @return $this
     */
    public function setCollapsible(bool $collapsible): static
    {
        $this->collapsible = (bool) $collapsible;

        $this->filterCollapsibleValue();

        return $this;
    }

    /**
     * @param array $permissions
     *
     * @return $this
     */
    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @internal
     *
     * @return array
     */
    public function &getChildrenByRef(): array
    {
        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        if (is_array($this->children) && count($this->children) > 0) {
            return true;
        }

        return false;
    }

    public function addChild(Data|Layout $child)
    {
        $this->children[] = $child;
    }

    /**
     * @param array $data
     * @param array $blockedKeys
     *
     * @return $this
     */
    public function setValues(array $data = [], array $blockedKeys = []): static
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = 'set' . ucfirst($key);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDatatype(): string
    {
        return $this->datatype;
    }

    /**
     * @param string $datatype
     *
     * @return $this
     */
    public function setDatatype(string $datatype): static
    {
        $this->datatype = $datatype;

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     *
     * @return $this
     */
    public function setLocked(bool $locked): static
    {
        $this->locked = (bool) $locked;

        return $this;
    }

    /**
     * @param bool $collapsed
     *
     * @return $this
     */
    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = (bool) $collapsed;

        $this->filterCollapsibleValue();

        return $this;
    }

    /**
     * @return bool
     */
    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }

    /**
     * @param string $bodyStyle
     *
     * @return $this
     */
    public function setBodyStyle(string $bodyStyle): static
    {
        $this->bodyStyle = $bodyStyle;

        return $this;
    }

    /**
     * @return string
     */
    public function getBodyStyle(): string
    {
        return $this->bodyStyle;
    }

    /**
     * @return Layout
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

    /**
     * {@inheritdoc}
     */
    public static function __set_state($data)
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }
}
