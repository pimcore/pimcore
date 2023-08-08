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

interface UserRoleInterface extends AbstractUserInterface
{
    /**
     * @return $this
     *
     * @internal
     *
     */
    public function setAllAclToFalse(): static;

    /**
     *
     * @return $this
     */
    public function setPermission(string $permissionName, bool $value = null): static;

    /**
     * @return string[]
     */
    public function getPermissions(): array;

    public function getPermission(string $permissionName): bool;

    /**
     * Generates the permission list required for frontend display
     *
     * @return array<string, bool>
     *
     * @internal
     *
     * @todo: $permissionInfo should be array, but is declared as null
     */
    public function generatePermissionList(): array;

    /**
     * @param string[]|string $permissions
     *
     * @return $this
     */
    public function setPermissions(array|string $permissions): static;

    /**
     * @param Asset[] $workspacesAsset
     *
     * @return $this
     */
    public function setWorkspacesAsset(array $workspacesAsset): static;

    /**
     * @return Asset[]
     */
    public function getWorkspacesAsset(): array;

    /**
     * @param Document[] $workspacesDocument
     *
     * @return $this
     */
    public function setWorkspacesDocument(array $workspacesDocument): static;

    /**
     * @return Document[]
     */
    public function getWorkspacesDocument(): array;

    /**
     * @param DataObject[] $workspacesObject
     *
     * @return $this
     */
    public function setWorkspacesObject(array $workspacesObject): static;

    /**
     * @return DataObject[]
     */
    public function getWorkspacesObject(): array;

    /**
     * @param string[]|string $classes
     *
     * @return $this
     */
    public function setClasses(array|string $classes): static;

    /**
     * @return string[]
     */
    public function getClasses(): array;

    /**
     * @param string[]|string $docTypes
     *
     * @return $this
     */
    public function setDocTypes(array|string $docTypes): static;

    /**
     * @return string[]
     */
    public function getDocTypes(): array;

    /**
     * @return string[]
     */
    public function getPerspectives(): array;

    /**
     * @param string[]|string $perspectives
     *
     * @return $this
     */
    public function setPerspectives(array|string $perspectives): static;

    /**
     * @return string[]
     */
    public function getWebsiteTranslationLanguagesView(): array;

    /**
     * @param string[]|string $websiteTranslationLanguagesView
     *
     * @return $this
     */
    public function setWebsiteTranslationLanguagesView(array|string $websiteTranslationLanguagesView): static;

    /**
     * @return string[]
     */
    public function getWebsiteTranslationLanguagesEdit(): array;

    /**
     * @param string[]|string $websiteTranslationLanguagesEdit
     *
     * @return $this
     */
    public function setWebsiteTranslationLanguagesEdit(array|string $websiteTranslationLanguagesEdit): static;
}
