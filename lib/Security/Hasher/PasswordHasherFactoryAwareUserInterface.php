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

namespace Pimcore\Security\Hasher;

/**
 * @internal
 */
interface PasswordHasherFactoryAwareUserInterface
{
    /**
     * Gets the name of the password hasher factory used to hash the password.
     *
     * If the method returns null, the standard way to retrieve the hasher
     * will be used instead.
     *
     */
    public function getHasherFactoryName(): string;
}
