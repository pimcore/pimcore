<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\Profile;

use Throwable;

/**
 * Optional interface for install profiles that need a post-install hook.
 *
 * Profiles that implement this interface will have their postInstall() method
 * called after all installation steps and post-install commands are complete.
 *
 * Profiles that don't need post-install logic should simply not implement
 * this interface.
 */
interface PostInstallHookInterface
{
    /**
     * Project-specific post-install hook (runs after all commands).
     *
     * @throws Throwable if the post-install hook fails
     */
    public function postInstall(PostInstallContext $context): void;
}
