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

namespace Pimcore\Model\User;

use Pimcore\Model\User\Workspace\Asset;
use Pimcore\Model\User\Workspace\DataObject;
use Pimcore\Model\User\Workspace\Document;

/**
 * @method \Pimcore\Model\User\UserRole\Dao getDao()
 */
abstract class UserRole extends AbstractUser implements UserRoleInterface
{
    /**
     * @internal
     *
     * @var string[]
     */
    protected array $permissions = [];

    /**
     * @internal
     *
     * @var Asset[]
     */
    protected array $workspacesAsset = [];

    /**
     * @internal
     *
     * @var DataObject[]
     */
    protected array $workspacesObject = [];

    /**
     * @internal
     *
     * @var Document[]
     */
    protected array $workspacesDocument = [];

    /**
     * @internal
     *
     * @var string[]
     */
    protected array $classes = [];

    /**
     * @internal
     *
     * @var string[]
     */
    protected array $docTypes = [];

    /**
     * @internal
     *
     * @var string[]
     */
    protected array $perspectives = [];

    /**
     * @internal
     *
     * @var string[]
     */
    protected array $websiteTranslationLanguagesView = [];

    /**
     * @internal
     *
     * @var string[]
     */
    protected array $websiteTranslationLanguagesEdit = [];

    protected function update(): void
    {
        $this->getDao()->update();

        // save all workspaces
        $this->getDao()->emptyWorkspaces();

        foreach ($this->getWorkspacesAsset() as $workspace) {
            $workspace->setUserId($this->getId());
            $workspace->save();
        }
        foreach ($this->getWorkspacesDocument() as $workspace) {
            $workspace->setUserId($this->getId());
            $workspace->save();
        }
        foreach ($this->getWorkspacesObject() as $workspace) {
            $workspace->setUserId($this->getId());
            $workspace->save();
        }
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function setAllAclToFalse(): static
    {
        $this->permissions = [];

        return $this;
    }

    /**
     *
     * @return $this
     */
    public function setPermission(string $permissionName, bool $value = null): static
    {
        if (!in_array($permissionName, $this->permissions) && $value) {
            $this->permissions[] = $permissionName;
        } elseif (in_array($permissionName, $this->permissions) && !$value) {
            $position = array_search($permissionName, $this->permissions);
            array_splice($this->permissions, $position, 1);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getPermission(string $permissionName): bool
    {
        if (in_array($permissionName, $this->permissions)) {
            return true;
        }

        return false;
    }

    /**
     * Generates the permission list required for frontend display
     *
     * @internal
     *
     * @return array<string, bool>
     *
     * @todo: $permissionInfo should be array, but is declared as null
     */
    public function generatePermissionList(): array
    {
        $permissionInfo = null;

        $list = new Permission\Definition\Listing();
        $definitions = $list->load();

        foreach ($definitions as $definition) {
            $permissionInfo[$definition->getKey()] = $this->getPermission($definition->getKey());
        }

        return $permissionInfo;
    }

    /**
     * @param string[]|string $permissions
     *
     * @return $this
     */
    public function setPermissions(array|string $permissions): static
    {
        if (is_string($permissions)) {
            $this->permissions = explode(',', $permissions);
        } elseif (is_array($permissions)) {
            $this->permissions = $permissions;
        }

        return $this;
    }

    /**
     * @param Asset[] $workspacesAsset
     *
     * @return $this
     */
    public function setWorkspacesAsset(array $workspacesAsset): static
    {
        $this->workspacesAsset = $workspacesAsset;

        return $this;
    }

    /**
     * @return Asset[]
     */
    public function getWorkspacesAsset(): array
    {
        return $this->workspacesAsset;
    }

    /**
     * @param Document[] $workspacesDocument
     *
     * @return $this
     */
    public function setWorkspacesDocument(array $workspacesDocument): static
    {
        $this->workspacesDocument = $workspacesDocument;

        return $this;
    }

    /**
     * @return Document[]
     */
    public function getWorkspacesDocument(): array
    {
        return $this->workspacesDocument;
    }

    /**
     * @param DataObject[] $workspacesObject
     *
     * @return $this
     */
    public function setWorkspacesObject(array $workspacesObject): static
    {
        $this->workspacesObject = $workspacesObject;

        return $this;
    }

    /**
     * @return DataObject[]
     */
    public function getWorkspacesObject(): array
    {
        return $this->workspacesObject;
    }

    /**
     * @param string[]|string $classes
     *
     * @return $this
     */
    public function setClasses(array|string $classes): static
    {
        $classes = $this->prepareArray($classes);

        $this->classes = $classes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @param string[]|string $docTypes
     *
     * @return $this
     */
    public function setDocTypes(array|string $docTypes): static
    {
        $docTypes = $this->prepareArray($docTypes);

        $this->docTypes = $docTypes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDocTypes(): array
    {
        return $this->docTypes;
    }

    /**
     * @return string[]
     */
    public function getPerspectives(): array
    {
        return $this->perspectives;
    }

    /**
     * @param string[]|string $perspectives
     *
     * @return $this
     */
    public function setPerspectives(array|string $perspectives): static
    {
        $perspectives = $this->prepareArray($perspectives);

        $this->perspectives = $perspectives;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getWebsiteTranslationLanguagesView(): array
    {
        return $this->websiteTranslationLanguagesView;
    }

    /**
     * @param string[]|string $websiteTranslationLanguagesView
     *
     * @return $this
     */
    public function setWebsiteTranslationLanguagesView(array|string $websiteTranslationLanguagesView): static
    {
        $websiteTranslationLanguagesView = $this->prepareArray($websiteTranslationLanguagesView);

        $this->websiteTranslationLanguagesView = $websiteTranslationLanguagesView;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getWebsiteTranslationLanguagesEdit(): array
    {
        return $this->websiteTranslationLanguagesEdit;
    }

    /**
     * @param string[]|string $websiteTranslationLanguagesEdit
     *
     * @return $this
     */
    public function setWebsiteTranslationLanguagesEdit(array|string $websiteTranslationLanguagesEdit): static
    {
        $websiteTranslationLanguagesEdit = $this->prepareArray($websiteTranslationLanguagesEdit);

        $this->websiteTranslationLanguagesEdit = $websiteTranslationLanguagesEdit;

        return $this;
    }

    /**
     * checks if given parameter is string and if so splits it creates array
     * returns empty array if empty parameter is given
     *
     * @param string[]|string $array
     *
     * @return string[]
     *
     * @internal
     */
    protected function prepareArray(array|string $array): array
    {
        if (is_string($array) && strlen($array)) {
            $array = explode(',', $array);
        }

        if (empty($array) || !is_array($array)) {
            $array = [];
        }

        return $array;
    }
}
